<?php

namespace App\Services;

use App\Models\AttendanceEmployee;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceRuleService
{
    public const GRACE_MINUTES = 15;
    public const LATES_BEFORE_HALF_DAY = 3;

    public static function reasonLabels(): array
    {
        return [
            AttendanceEmployee::REASON_ON_TIME => 'On Time',
            AttendanceEmployee::REASON_LATE_MARK => 'Late Mark',
            AttendanceEmployee::REASON_HALF_DAY_LATE_MARK => 'Half Day – Due to Late Mark',
            AttendanceEmployee::REASON_HALF_DAY_INSUFFICIENT_HOURS => 'Half Day – Due to Insufficient Working Hours',
            AttendanceEmployee::REASON_HALF_DAY_MISSING_PUNCH_OUT => 'Half Day – Due to Missing Punch-Out',
            AttendanceEmployee::REASON_SINGLE_PUNCH => 'Single Punch In',
            AttendanceEmployee::REASON_ABSENT => 'Absent',
            AttendanceEmployee::REASON_MANUAL => 'Half Day',
        ];
    }

    public static function reasonLabel(?string $reason, ?string $fallbackStatus = null): string
    {
        $labels = self::reasonLabels();
        if ($reason && isset($labels[$reason])) {
            return $labels[$reason];
        }

        if ($fallbackStatus) {
            return $fallbackStatus;
        }

        return 'Present';
    }

    public function evaluate(Employee $employee, string $date, ?string $clockIn, ?string $clockOut, int $latesAlreadyInCycle = 0, array $options = []): array
    {
        $forceStatus = $options['force_status'] ?? null;
        $forceReason = $options['force_reason'] ?? null;
        $today = Carbon::today()->format('Y-m-d');

        $late = $this->lateDuration($employee, $clockIn, $date);
        $earlyLeaving = $this->earlyLeaving($employee, $clockOut, $date);
        $overtime = $this->overtime($employee, $clockOut, $date);
        $isLate = $late !== '00:00:00';
        $hasClockIn = $this->hasTime($clockIn);
        $hasClockOut = $this->hasTime($clockOut);

        $status = AttendanceEmployee::STATUS_PRESENT;
        $reason = AttendanceEmployee::REASON_ON_TIME;
        $lateCycleNumber = null;
        $resetsCycle = false;

        if (!$hasClockIn) {
            $status = AttendanceEmployee::STATUS_ABSENT;
            $reason = AttendanceEmployee::REASON_ABSENT;
        } elseif (!$hasClockOut) {
            if ($date >= $today) {
                $status = AttendanceEmployee::STATUS_SINGLE_PUNCH;
                $reason = $isLate ? AttendanceEmployee::REASON_LATE_MARK : AttendanceEmployee::REASON_ON_TIME;
            } else {
                $status = AttendanceEmployee::STATUS_HALF_DAY;
                $reason = AttendanceEmployee::REASON_HALF_DAY_MISSING_PUNCH_OUT;
            }
        } else {
            $workedHours = $this->workedHours($clockIn, $clockOut, $date);
            if ($workedHours < AttendanceEmployee::REQUIRED_WORKING_HOURS) {
                $status = AttendanceEmployee::STATUS_HALF_DAY;
                $reason = AttendanceEmployee::REASON_HALF_DAY_INSUFFICIENT_HOURS;
            } elseif ($isLate) {
                $reason = AttendanceEmployee::REASON_LATE_MARK;
            } else {
                $reason = AttendanceEmployee::REASON_ON_TIME;
            }
        }

        if ($isLate && $hasClockIn) {
            $position = $latesAlreadyInCycle + 1;
            if ($position > self::LATES_BEFORE_HALF_DAY) {
                $lateCycleNumber = 4;
                $resetsCycle = true;
                if ($status === AttendanceEmployee::STATUS_PRESENT) {
                    $status = AttendanceEmployee::STATUS_HALF_DAY;
                    $reason = AttendanceEmployee::REASON_HALF_DAY_LATE_MARK;
                }
            } else {
                $lateCycleNumber = $position;
            }
        }

        if ($forceReason === AttendanceEmployee::REASON_HALF_DAY_MISSING_PUNCH_OUT) {
            $status = AttendanceEmployee::STATUS_HALF_DAY;
            $reason = AttendanceEmployee::REASON_HALF_DAY_MISSING_PUNCH_OUT;
        }

        if ($forceStatus === AttendanceEmployee::STATUS_HALF_DAY && !$forceReason) {
            $status = AttendanceEmployee::STATUS_HALF_DAY;
            if ($reason === AttendanceEmployee::REASON_ON_TIME || $reason === AttendanceEmployee::REASON_LATE_MARK) {
                $reason = AttendanceEmployee::REASON_MANUAL;
            }
        }

        if ($forceStatus === AttendanceEmployee::STATUS_PRESENT) {
            $status = AttendanceEmployee::STATUS_PRESENT;
            if ($reason !== AttendanceEmployee::REASON_LATE_MARK && $reason !== AttendanceEmployee::REASON_ON_TIME) {
                $reason = $isLate ? AttendanceEmployee::REASON_LATE_MARK : AttendanceEmployee::REASON_ON_TIME;
            }
        }

        $label = self::reasonLabel($reason, $status);
        if ($reason === AttendanceEmployee::REASON_LATE_MARK && $lateCycleNumber) {
            $label = 'Late Mark ' . $lateCycleNumber;
        }

        return [
            'status' => $status,
            'status_reason' => $reason,
            'late' => $late,
            'early_leaving' => $earlyLeaving,
            'overtime' => $overtime,
            'is_late' => $isLate,
            'late_cycle_number' => $lateCycleNumber,
            'resets_cycle' => $resetsCycle,
            'label' => $label,
        ];
    }

    public function apply(AttendanceEmployee $attendance, array $options = []): array
    {
        $employee = $options['employee'] ?? $attendance->employee;
        if (!$employee) {
            $employee = Employee::find($attendance->employee_id);
        }
        if (!$employee) {
            return [];
        }

        $date = Carbon::parse($attendance->date)->format('Y-m-d');
        $latesInCycle = $this->latesInCurrentCycle((int) $attendance->employee_id, $date);
        $result = $this->evaluate(
            $employee,
            $date,
            $attendance->clock_in,
            $attendance->clock_out,
            $latesInCycle,
            $options
        );

        $attendance->late = $result['late'];
        $attendance->early_leaving = $result['early_leaving'];
        if ($result['overtime'] !== null) {
            $attendance->overtime = $result['overtime'];
        }
        $attendance->status = $result['status'];
        $attendance->status_reason = $result['status_reason'];
        $attendance->late_cycle_number = $result['late_cycle_number'];

        return $result;
    }

    public function applyAndSave(AttendanceEmployee $attendance, array $options = []): array
    {
        $result = $this->apply($attendance, $options);
        $attendance->save();

        $date = Carbon::parse($attendance->date)->format('Y-m-d');
        $this->recalculateFrom((int) $attendance->employee_id, Carbon::parse($date)->addDay()->format('Y-m-d'));

        return $result;
    }

    public function recalculateFrom(int $employeeId, string $fromDate): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return;
        }

        $records = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('date', '>=', $fromDate)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $latesInCycle = $this->latesInCurrentCycle($employeeId, $fromDate);
        $currentMonth = Carbon::parse($fromDate)->format('Y-m');

        foreach ($records as $record) {
            $date = Carbon::parse($record->date)->format('Y-m-d');
            $month = Carbon::parse($date)->format('Y-m');
            if ($month !== $currentMonth) {
                $latesInCycle = 0;
                $currentMonth = $month;
            }
            $result = $this->evaluate($employee, $date, $record->clock_in, $record->clock_out, $latesInCycle);

            $dirty = $record->status !== $result['status']
                || $record->status_reason !== $result['status_reason']
                || $record->late !== $result['late']
                || (string) $record->late_cycle_number !== (string) $result['late_cycle_number']
                || $record->early_leaving !== $result['early_leaving'];

            if ($dirty) {
                $record->late = $result['late'];
                $record->early_leaving = $result['early_leaving'];
                $record->status = $result['status'];
                $record->status_reason = $result['status_reason'];
                $record->late_cycle_number = $result['late_cycle_number'];
                $record->save();
            }

            if (!empty($result['is_late'])) {
                $latesInCycle = !empty($result['resets_cycle']) ? 0 : $latesInCycle + 1;
            }
        }
    }

    /**
     * Count late marks in the current calendar month only (before $beforeDate).
     * Cycle resets every month, and also after the 4th late (half day) within that month.
     */
    public function latesInCurrentCycle(int $employeeId, string $beforeDate): int
    {
        $before = Carbon::parse($beforeDate)->startOfDay();
        $monthStart = $before->copy()->startOfMonth()->format('Y-m-d');
        $beforeStr = $before->format('Y-m-d');

        $lastReset = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('date', '>=', $monthStart)
            ->where('date', '<', $beforeStr)
            ->where(function ($query) {
                $query->where('late_cycle_number', 4)
                    ->orWhere('status_reason', AttendanceEmployee::REASON_HALF_DAY_LATE_MARK);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        $query = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('date', '>=', $monthStart)
            ->where('date', '<', $beforeStr)
            ->whereNotNull('clock_in')
            ->where('clock_in', '!=', '')
            ->where('clock_in', '!=', '00:00:00')
            ->whereNotNull('late')
            ->where('late', '!=', '00:00:00');

        if ($lastReset) {
            $query->where('date', '>', Carbon::parse($lastReset->date)->format('Y-m-d'));
        }

        return $query->count();
    }

    public function lateDuration(?Employee $employee, ?string $clockIn, string $date): string
    {
        if (!$this->hasTime($clockIn)) {
            return '00:00:00';
        }

        $clockInTime = Carbon::parse($date . ' ' . $clockIn);
        $threshold = $this->lateThreshold($employee, $date);

        if ($clockInTime->gt($threshold)) {
            return gmdate('H:i:s', $clockInTime->diffInSeconds($threshold));
        }

        return '00:00:00';
    }

    public function earlyLeaving(?Employee $employee, ?string $clockOut, string $date): string
    {
        if (!$this->hasTime($clockOut)) {
            return '00:00:00';
        }

        $clockOutTime = Carbon::parse($date . ' ' . $clockOut);
        $end = Carbon::parse($date . ' ' . $this->shiftEnd($employee));

        if ($clockOutTime->lt($end)) {
            return gmdate('H:i:s', $end->diffInSeconds($clockOutTime));
        }

        return '00:00:00';
    }

    public function overtime(?Employee $employee, ?string $clockOut, string $date): string
    {
        if (!$this->hasTime($clockOut)) {
            return '00:00:00';
        }

        $clockOutTime = Carbon::parse($date . ' ' . $clockOut);
        $end = Carbon::parse($date . ' ' . $this->shiftEnd($employee));

        if ($clockOutTime->gt($end)) {
            return gmdate('H:i:s', $clockOutTime->diffInSeconds($end));
        }

        return '00:00:00';
    }

    public function lateThreshold(?Employee $employee, string $date): Carbon
    {
        $start = $this->shiftStart($employee);

        return Carbon::parse($date . ' ' . $start)->addMinutes(self::GRACE_MINUTES);
    }

    public function shiftStart(?Employee $employee): string
    {
        return $employee ? $employee->shiftStartTime() : Employee::SHIFT_TIMINGS['first']['start'];
    }

    public function shiftEnd(?Employee $employee): string
    {
        return $employee ? $employee->shiftEndTime() : Employee::SHIFT_TIMINGS['first']['end'];
    }

    public function evaluateSequence(Employee $employee, iterable $attendances): array
    {
        $latesInCycle = 0;
        $currentMonth = null;
        $mapped = [];

        foreach ($attendances as $attendance) {
            $date = Carbon::parse(is_array($attendance) ? $attendance['date'] : $attendance->date)->format('Y-m-d');
            $month = Carbon::parse($date)->format('Y-m');
            if ($currentMonth !== null && $currentMonth !== $month) {
                $latesInCycle = 0;
            }
            $currentMonth = $month;
            $clockIn = is_array($attendance) ? ($attendance['clock_in'] ?? null) : $attendance->clock_in;
            $clockOut = is_array($attendance) ? ($attendance['clock_out'] ?? null) : $attendance->clock_out;
            $storedStatus = is_array($attendance) ? ($attendance['status'] ?? null) : $attendance->status;

            $result = $this->evaluate($employee, $date, $clockIn, $clockOut, $latesInCycle);

            $type = 'present';
            if ($result['status'] === AttendanceEmployee::STATUS_ABSENT) {
                $type = 'absent';
            } elseif ($result['status'] === AttendanceEmployee::STATUS_SINGLE_PUNCH) {
                $type = 'single_punch';
            } elseif ($result['status'] === AttendanceEmployee::STATUS_HALF_DAY) {
                $type = 'half_day';
            } elseif ($result['is_late']) {
                $type = 'late';
            }

            $mapped[$date] = [
                'type' => $type,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'is_late' => $result['is_late'],
                'late_time' => $result['late'],
                'status_reason' => $result['status_reason'],
                'status_label' => $result['label'],
                'late_cycle_number' => $result['late_cycle_number'],
                'raw_status' => $result['status'] ?: $storedStatus,
            ];

            if (!empty($result['is_late'])) {
                $latesInCycle = !empty($result['resets_cycle']) ? 0 : $latesInCycle + 1;
            }
        }

        return $mapped;
    }

    protected function workedHours(?string $clockIn, ?string $clockOut, string $date): float
    {
        if (!$this->hasTime($clockIn) || !$this->hasTime($clockOut)) {
            return 0;
        }

        $start = Carbon::parse($date . ' ' . $clockIn);
        $end = Carbon::parse($date . ' ' . $clockOut);
        if ($end->lt($start)) {
            $end->addDay();
        }

        return $end->diffInSeconds($start) / 3600;
    }

    protected function hasTime(?string $time): bool
    {
        return !empty($time) && $time !== '00:00:00' && $time !== '00:00';
    }
}
