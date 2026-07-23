<?php

namespace App\Observers;

use App\Models\AttendanceEmployee;
use App\Services\AttendanceLeaveReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceEmployeeObserver
{
    public function saved(AttendanceEmployee $attendance): void
    {
        if ($attendance->status !== AttendanceEmployee::STATUS_PRESENT) {
            return;
        }

        if (!$this->hasValidClockIn($attendance)) {
            return;
        }

        $date = \Carbon\Carbon::parse($attendance->date)->format('Y-m-d');
        $employeeId = (int) $attendance->employee_id;

        try {
            DB::afterCommit(function () use ($employeeId, $date, $attendance) {
                DB::transaction(function () use ($employeeId, $date, $attendance) {
                    app(AttendanceLeaveReconciliationService::class)->reconcilePresentDay($employeeId, $date, [
                        'attendance_id' => $attendance->id,
                        'source' => 'attendance_saved',
                    ]);
                });
            });
        } catch (\Throwable $e) {
            Log::error('attendance_leave_reconciliation_failed', [
                'message' => $e->getMessage(),
                'attendance_id' => $attendance->id,
                'employee_id' => $employeeId,
                'date' => $date,
            ]);
        }
    }

    protected function hasValidClockIn(AttendanceEmployee $attendance): bool
    {
        $ci = $attendance->clock_in;

        return $ci !== null && $ci !== '' && $ci !== '00:00:00';
    }
}
