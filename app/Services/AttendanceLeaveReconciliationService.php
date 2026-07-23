<?php

namespace App\Services;

use App\Models\Leave as LocalLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * When attendance is saved as Present with a valid clock-in, removes or splits
 * overlapping approved leaves and restores regular monthly balances.
 *
 * Comp-Off leave: removing/splitting rows only changes the sum of approved
 * total_leave_days (earned comp_off_leaves rows are not auto-inserted here).
 */
class AttendanceLeaveReconciliationService
{
    public function reconcilePresentDay(int $employeeId, string $dateYmd, array $context = []): array
    {
        $result = [
            'removed_leaves' => 0,
            'split_operations' => 0,
            'regular_days_restored' => 0.0,
            'notes' => [],
        ];

        $leaves = LocalLeave::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->where('start_date', '<=', $dateYmd)
            ->where('end_date', '>=', $dateYmd)
            ->with('leaveType')
            ->orderBy('id')
            ->get();

        foreach ($leaves as $leave) {
            $this->processLeave($leave, $dateYmd, $result);
        }

        $this->writeAuditLog($employeeId, $dateYmd, $result, $context);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function processLeave(LocalLeave $leave, string $dateYmd, array &$result): void
    {
        $type = $leave->leaveType;
        $title = $type ? (string) $type->title : '';

        if ($title === 'Leave Without Pay') {
            $leave->delete();
            $result['removed_leaves']++;
            return;
        }

        $start = Carbon::parse($leave->start_date)->format('Y-m-d');
        $end = Carbon::parse($leave->end_date)->format('Y-m-d');
        $duration = $leave->leave_duration_type ?? 'full_day';

        if ($duration === 'half_day' && $start === $end && $start === $dateYmd) {
            $days = (float) $leave->total_leave_days;
            $this->restoreRegularLeaveIfNeeded($leave, $days);
            $leave->delete();
            $result['removed_leaves']++;
            $result['regular_days_restored'] += $this->regularRestoreAmount($leave, $days);
            return;
        }

        if ($duration === 'full_day' && $start === $end && $start === $dateYmd) {
            $days = (float) $leave->total_leave_days;
            $this->restoreRegularLeaveIfNeeded($leave, $days);
            $leave->delete();
            $result['removed_leaves']++;
            $result['regular_days_restored'] += $this->regularRestoreAmount($leave, $days);
            return;
        }

        if ($duration !== 'full_day') {
            $days = (float) $leave->total_leave_days;
            $this->restoreRegularLeaveIfNeeded($leave, $days);
            $leave->delete();
            $result['removed_leaves']++;
            $result['notes'][] = 'Removed multi-day non-full_day leave #' . $leave->id . ' entirely (Present on ' . $dateYmd . ')';
            $result['regular_days_restored'] += $this->regularRestoreAmount($leave, $days);
            return;
        }

        $this->splitFullDayLeave($leave, $dateYmd, $result);
    }

    protected function splitFullDayLeave(LocalLeave $leave, string $dateYmd, array &$result): void
    {
        $employeeId = (int) $leave->employee_id;
        $S = Carbon::parse($leave->start_date)->startOfDay();
        $E = Carbon::parse($leave->end_date)->startOfDay();
        $cd = Carbon::parse($dateYmd)->startOfDay();

        if ($cd->lt($S) || $cd->gt($E)) {
            return;
        }

        $oldTotal = (float) $leave->total_leave_days;
        $segments = [];

        if ($S->format('Y-m-d') !== $dateYmd) {
            $segEnd = $cd->copy()->subDay();
            if ($segEnd->gte($S)) {
                $segments[] = [$S->format('Y-m-d'), $segEnd->format('Y-m-d')];
            }
        }

        if ($E->format('Y-m-d') !== $dateYmd) {
            $segStart = $cd->copy()->addDay();
            if ($segStart->lte($E)) {
                $segments[] = [$segStart->format('Y-m-d'), $E->format('Y-m-d')];
            }
        }

        $newTotal = 0.0;
        foreach ($segments as [$a, $b]) {
            $newTotal += LeaveLedgerService::calculateWorkingDays($employeeId, $a, $b);
        }

        $isCompOff = $leave->leaveType && $leave->leaveType->title === 'Comp-Off';

        if (!$isCompOff) {
            LeaveLedgerService::restoreRegularLeaveBalance($employeeId, (int) $leave->leave_type_id, $oldTotal);
        }

        $createdBy = (int) $leave->created_by;
        $leaveTypeId = (int) $leave->leave_type_id;
        $reasonBase = (string) ($leave->leave_reason ?? '');
        $remark = $leave->remark;

        $leave->delete();

        foreach ($segments as [$a, $b]) {
            $t = LeaveLedgerService::calculateWorkingDays($employeeId, $a, $b);
            if ($t <= 0) {
                continue;
            }

            LocalLeave::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'applied_on' => now()->toDateString(),
                'start_date' => $a,
                'end_date' => $b,
                'total_leave_days' => $t,
                'leave_duration_type' => 'full_day',
                'half_day_session' => null,
                'leave_reason' => $reasonBase . ' | ' . __('Auto-adjusted: employee marked present on :date', ['date' => $dateYmd]),
                'remark' => $remark,
                'status' => 'Approved',
                'created_by' => $createdBy,
            ]);

            if (!$isCompOff) {
                LeaveLedgerService::applyRegularLeaveBalanceDeduction($employeeId, $leaveTypeId, (float) $t);
            }
        }

        $result['split_operations']++;
    }

    protected function restoreRegularLeaveIfNeeded(LocalLeave $leave, float $days): void
    {
        if ($this->isCompOff($leave) || $this->isLeaveWithoutPay($leave)) {
            return;
        }
        LeaveLedgerService::restoreRegularLeaveBalance((int) $leave->employee_id, (int) $leave->leave_type_id, $days);
    }

    protected function regularRestoreAmount(LocalLeave $leave, float $days): float
    {
        if ($this->isCompOff($leave) || $this->isLeaveWithoutPay($leave)) {
            return 0.0;
        }

        return $days;
    }

    protected function isCompOff(LocalLeave $leave): bool
    {
        return $leave->leaveType && $leave->leaveType->title === 'Comp-Off';
    }

    protected function isLeaveWithoutPay(LocalLeave $leave): bool
    {
        return $leave->leaveType && $leave->leaveType->title === 'Leave Without Pay';
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $context
     */
    protected function writeAuditLog(int $employeeId, string $dateYmd, array $result, array $context): void
    {
        if ($result['removed_leaves'] === 0 && $result['split_operations'] === 0) {
            return;
        }

        Log::info('attendance_leave_reconciliation', array_merge([
            'employee_id' => $employeeId,
            'date' => $dateYmd,
            'removed_leaves' => $result['removed_leaves'],
            'split_operations' => $result['split_operations'],
            'regular_days_restored' => $result['regular_days_restored'],
            'notes' => $result['notes'],
        ], $context));
    }
}
