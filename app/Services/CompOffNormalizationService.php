<?php

namespace App\Services;

use App\Models\AttendanceEmployee;
use App\Models\CompOffLeave;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Normalizes comp_off_leaves for a tenant and calendar year against current
 * week_off_day and attendance (Present / Half Day / Single Punch In, valid clock-in).
 */
class CompOffNormalizationService
{
    /** Same exclusion list as other comp-off processors in this project */
    public const EXCLUDED_EMPLOYEE_IDS = [];

    public const REPORT_SAMPLE_LIMIT = 150;

    /**
     * Single source of truth for “earned comp off” attendance eligibility.
     */
    public static function attendanceQualifiesForEarnedCompOff(?AttendanceEmployee $attendance): bool
    {
        if (!$attendance) {
            return false;
        }

        $clockIn = $attendance->clock_in;
        if ($clockIn === null || $clockIn === '' || $clockIn === '00:00:00') {
            return false;
        }

        return in_array($attendance->status, [
            AttendanceEmployee::STATUS_PRESENT,
            AttendanceEmployee::STATUS_HALF_DAY,
            AttendanceEmployee::STATUS_SINGLE_PUNCH,
        ], true);
    }

    public static function dateMatchesEmployeeWeekOff(string $dateYmd, string $weekOffNormalized): bool
    {
        $weekOffNormalized = strtolower(trim($weekOffNormalized));
        if ($weekOffNormalized === '') {
            return false;
        }

        $dow = strtolower(Carbon::parse($dateYmd)->format('l'));

        return $dow === $weekOffNormalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeYearForTenant(int $year, int $creatorId, bool $dryRun = false): array
    {
        $report = [
            'year' => $year,
            'creator_id' => $creatorId,
            'dry_run' => $dryRun,
            'employees_scanned' => 0,
            'employees_skipped' => [],
            'rows_created' => 0,
            'rows_removed' => 0,
            'employees_with_changes' => 0,
            'sample_removed' => [],
            'sample_created' => [],
            'errors' => [],
        ];

        $dateFrom = Carbon::create($year, 1, 1)->format('Y-m-d');
        $dateTo = Carbon::create($year, 12, 31)->format('Y-m-d');

        $employees = Employee::query()
            ->where('created_by', $creatorId)
            ->whereHas('user', function ($q) {
                $q->where('type', 'employee');
            })
            ->notTerminated()
            ->orderBy('id')
            ->get();

        foreach ($employees as $employee) {
            if (in_array($employee->id, self::EXCLUDED_EMPLOYEE_IDS, true)) {
                $report['employees_skipped'][] = [
                    'employee_id' => $employee->id,
                    'reason' => 'Excluded by fixed policy list',
                ];
                continue;
            }

            $weekOffNorm = strtolower(trim((string) ($employee->week_off_day ?? '')));
            if ($weekOffNorm === '') {
                $report['employees_skipped'][] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name ?? trim($employee->name . ' ' . ($employee->last_name ?? '')),
                    'reason' => 'No week_off_day configured',
                ];
                continue;
            }

            $createdBefore = $report['rows_created'];
            $removedBefore = $report['rows_removed'];

            try {
                if ($dryRun) {
                    $this->normalizeEmployeeYear($employee, $weekOffNorm, $dateFrom, $dateTo, true, $report);
                } else {
                    DB::transaction(function () use ($employee, $weekOffNorm, $dateFrom, $dateTo, &$report) {
                        $this->normalizeEmployeeYear($employee, $weekOffNorm, $dateFrom, $dateTo, false, $report);
                    });
                }
            } catch (\Throwable $e) {
                $report['errors'][] = [
                    'employee_id' => $employee->id,
                    'message' => $e->getMessage(),
                ];
            }

            if ($report['rows_created'] > $createdBefore || $report['rows_removed'] > $removedBefore) {
                $report['employees_with_changes']++;
            }

            $report['employees_scanned']++;
        }

        Log::info('CompOff normalization completed', [
            'year' => $year,
            'creator_id' => $creatorId,
            'dry_run' => $dryRun,
            'rows_created' => $report['rows_created'],
            'rows_removed' => $report['rows_removed'],
            'employees_scanned' => $report['employees_scanned'],
            'errors' => count($report['errors']),
        ]);

        return $report;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function normalizeEmployeeYear(
        Employee $employee,
        string $weekOffNorm,
        string $dateFrom,
        string $dateTo,
        bool $dryRun,
        array &$report
    ): void {
        $attendances = AttendanceEmployee::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get();

        $validEarnDates = [];
        foreach ($attendances as $att) {
            $dateKey = Carbon::parse($att->date)->format('Y-m-d');
            if (!self::dateMatchesEmployeeWeekOff($dateKey, $weekOffNorm)) {
                continue;
            }
            if (!self::attendanceQualifiesForEarnedCompOff($att)) {
                continue;
            }
            $validEarnDates[$dateKey] = true;
        }

        $compRows = CompOffLeave::query()
            ->where('employees_id', $employee->id)
            ->whereBetween('comp_off_date', [$dateFrom, $dateTo])
            ->get();

        foreach ($compRows as $row) {
            $d = Carbon::parse($row->comp_off_date)->format('Y-m-d');
            if (!isset($validEarnDates[$d])) {
                if (!$dryRun) {
                    $row->delete();
                }
                $report['rows_removed']++;
                if (count($report['sample_removed']) < self::REPORT_SAMPLE_LIMIT) {
                    $report['sample_removed'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name ?? $employee->name,
                        'comp_off_leave_id' => $row->id,
                        'comp_off_date' => $d,
                        'reason' => 'No qualifying week-off attendance for current week_off_day',
                    ];
                }
            }
        }

        foreach (array_keys($validEarnDates) as $d) {
            $exists = CompOffLeave::query()
                ->where('employees_id', $employee->id)
                ->where('comp_off_date', $d)
                ->exists();

            if (!$exists) {
                if (!$dryRun) {
                    CompOffLeave::create([
                        'employees_id' => $employee->id,
                        'comp_off_date' => $d,
                        'comp_off_data' => 1.0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $report['rows_created']++;
                if (count($report['sample_created']) < self::REPORT_SAMPLE_LIMIT) {
                    $report['sample_created'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name ?? $employee->name,
                        'comp_off_date' => $d,
                    ];
                }
            }
        }
    }
}
