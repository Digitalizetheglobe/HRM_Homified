<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PunchLocation extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'created_by',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_punch_location', 'punch_location_id', 'employee_id');
    }

    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    /**
     * Employees with no assigned locations can punch from anywhere.
     * Employees with one or more locations must be inside at least one radius.
     */
    public static function punchInBlockMessage(Employee $employee, float $latitude, float $longitude): ?string
    {
        $locations = $employee->punchLocations;
        if ($locations->isEmpty()) {
            return null;
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = self::distanceMeters(
                $latitude,
                $longitude,
                (float) $location->latitude,
                (float) $location->longitude
            );
            $radius = max(1, (int) ($location->radius_meters ?: 20));

            if ($distance <= $radius) {
                return null;
            }

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = $location;
            }
        }

        $meters = (int) round($nearestDistance ?? 0);
        $name = $nearest->name ?? __('assigned location');
        $radius = max(1, (int) ($nearest->radius_meters ?? 20));

        return __('Punch In is allowed only within :radius meters of your assigned location. You are about :meters meters from :name.', [
            'radius' => $radius,
            'meters' => $meters,
            'name' => $name,
        ]);
    }
}
