<?php

namespace App\Helpers;

/**
 * Turns noisy indoor GPS pings into stay-points (real visits) so the map
 * does not draw a spider-web of satellite drift around one office.
 */
class GpsTrackCleaner
{
    /** Points within this radius are treated as the same place (indoor GPS often drifts 30–150 m). */
    public const STAY_RADIUS_M = 100;

    /** A single jump faster than this is almost never real movement on foot/bike/car in city traffic. */
    public const MAX_SPEED_KMH = 120;

    /** Need this many consecutive pings away from a stay before we treat it as a real departure. */
    public const CONFIRM_AWAY_POINTS = 2;

    public static function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return 6371000 * $c;
    }

    public static function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        if ($hours > 0) {
            return $hours . 'h';
        }

        return $minutes . 'm';
    }

    /**
     * Collapse a chronological list of GPS points into visited stays.
     *
     * @param  array<int, array<string, mixed>>  $points
     * @return array<int, array<string, mixed>>
     */
    public static function toStayPoints(array $points): array
    {
        if (count($points) === 0) {
            return [];
        }

        usort($points, function ($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });

        $stays = [];
        $n = count($points);
        $i = 0;

        while ($i < $n) {
            $cluster = [$points[$i]];
            $j = $i + 1;

            while ($j < $n) {
                $centroid = self::medianLatLng($cluster);
                $candidate = $points[$j];
                $dist = self::haversineMeters(
                    $centroid['lat'],
                    $centroid['lng'],
                    (float) $candidate['lat'],
                    (float) $candidate['lng']
                );

                $prev = $cluster[count($cluster) - 1];
                $stepDist = self::haversineMeters(
                    (float) $prev['lat'],
                    (float) $prev['lng'],
                    (float) $candidate['lat'],
                    (float) $candidate['lng']
                );
                $dt = max(1, ((int) ($candidate['timestamp'] ?? 0)) - ((int) ($prev['timestamp'] ?? 0)));
                $speedKmh = ($stepDist / $dt) * 3.6;

                if ($dist > self::STAY_RADIUS_M && $speedKmh > self::MAX_SPEED_KMH) {
                    $j++;
                    continue;
                }

                if ($dist <= self::STAY_RADIUS_M) {
                    $cluster[] = $candidate;
                    $j++;
                    continue;
                }

                $decision = self::departureDecision($centroid, $points, $j, $n);
                if ($decision['action'] === 'skip_to') {
                    $j = $decision['index'];
                    continue;
                }
                if ($decision['action'] === 'absorb_rest') {
                    $j = $n;
                    break;
                }

                break;
            }

            $stays[] = self::buildStay($cluster, $points[$i]);
            $i = $j;
        }

        foreach ($stays as $idx => &$stay) {
            $stay['index'] = $idx + 1;
        }
        unset($stay);

        return $stays;
    }

    /**
     * Sum distance only between distinct stays (not raw GPS jitter).
     *
     * @param  array<int, array<string, mixed>>  $stays
     */
    public static function stayPathDistanceKm(array $stays): float
    {
        $meters = 0.0;
        for ($i = 1; $i < count($stays); $i++) {
            $meters += self::haversineMeters(
                (float) $stays[$i - 1]['lat'],
                (float) $stays[$i - 1]['lng'],
                (float) $stays[$i]['lat'],
                (float) $stays[$i]['lng']
            );
        }

        return round($meters / 1000, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cluster
     * @return array{lat: float, lng: float}
     */
    private static function medianLatLng(array $cluster): array
    {
        $lats = array_column($cluster, 'lat');
        $lngs = array_column($cluster, 'lng');
        sort($lats, SORT_NUMERIC);
        sort($lngs, SORT_NUMERIC);
        $mid = intdiv(count($lats), 2);

        return [
            'lat' => (float) $lats[$mid],
            'lng' => (float) $lngs[$mid],
        ];
    }

    /**
     * @param  array{lat: float, lng: float}  $centroid
     * @param  array<int, array<string, mixed>>  $points
     * @return array{action: string, index?: int}
     */
    private static function departureDecision(array $centroid, array $points, int $j, int $n): array
    {
        $away = 0;
        $k = $j;

        while ($k < $n && $away < self::CONFIRM_AWAY_POINTS) {
            $d = self::haversineMeters(
                $centroid['lat'],
                $centroid['lng'],
                (float) $points[$k]['lat'],
                (float) $points[$k]['lng']
            );

            $prev = $points[max(0, $k - 1)];
            $stepDist = self::haversineMeters(
                (float) $prev['lat'],
                (float) $prev['lng'],
                (float) $points[$k]['lat'],
                (float) $points[$k]['lng']
            );
            $dt = max(1, ((int) ($points[$k]['timestamp'] ?? 0)) - ((int) ($prev['timestamp'] ?? 0)));
            $speedKmh = ($stepDist / $dt) * 3.6;

            if ($d > self::STAY_RADIUS_M && $speedKmh > self::MAX_SPEED_KMH) {
                $k++;
                continue;
            }

            if ($d <= self::STAY_RADIUS_M) {
                return ['action' => 'skip_to', 'index' => $k];
            }

            $away++;
            $k++;
        }

        if ($away < self::CONFIRM_AWAY_POINTS) {
            return ['action' => 'absorb_rest'];
        }

        return ['action' => 'depart'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $cluster
     * @param  array<string, mixed>  $seed
     * @return array<string, mixed>
     */
    private static function buildStay(array $cluster, array $seed): array
    {
        $centroid = self::medianLatLng($cluster);
        $first = $cluster[0];
        $last = $cluster[count($cluster) - 1];
        $dwell = max(0, ((int) ($last['timestamp'] ?? 0)) - ((int) ($first['timestamp'] ?? 0)));

        return [
            'id' => $first['id'] ?? null,
            'lat' => $centroid['lat'],
            'lng' => $centroid['lng'],
            'time' => $first['time'] ?? '',
            'left_time' => $last['time'] ?? ($first['time'] ?? ''),
            'full_time' => $first['full_time'] ?? '',
            'timestamp' => $first['timestamp'] ?? 0,
            'last_timestamp' => $last['timestamp'] ?? ($first['timestamp'] ?? 0),
            'diff' => $last['diff'] ?? ($first['diff'] ?? ''),
            'type' => 'stay',
            'dwell_seconds' => $dwell,
            'dwell_label' => self::formatDuration($dwell),
            'ping_count' => count($cluster),
        ];
    }
}
