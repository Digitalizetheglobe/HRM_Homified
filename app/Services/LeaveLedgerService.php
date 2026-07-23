<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Centralized leave balance and comp-off credit restoration / deduction.
 */
class LeaveLedgerService
{
    /**
     * Working days between start and end (inclusive of end), excluding employee week off
     * (same logic as legacy LeaveController::calculateWorkingDays).
     */
    public static function calculateWorkingDays(int $employeeId, string $startDate, string $endDate): int
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return 0;
        }

        $weekOff = strtolower(trim((string) ($employee->week_off_day ?? '')));
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->add(new \DateInterval('P1D'));

        $workingDays = 0;
        $currentDate = clone $start;

        while ($currentDate < $end) {
            $dayName = strtolower($currentDate->format('l'));

            $isWeekOff = false;
            if ($weekOff !== '') {
                $isWeekOff = ($dayName === $weekOff);
            } else {
                $isWeekOff = in_array($dayName, ['saturday', 'sunday'], true);
            }

            if (!$isWeekOff) {
                $workingDays++;
            }

            $currentDate->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Restore comp-off “earned” rows (legacy behaviour: calendar walk + padding).
     */
    public static function restoreCompOffBalance(int $employeeId, float $totalDays, string $startDate, string $endDate): void
    {
        $daysToRestore = (int) ceil($totalDays - 1e-9);
        if ($daysToRestore <= 0) {
            return;
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        $currentDate = clone $start;
        $restoredCount = 0;

        while ($currentDate <= $end && $restoredCount < $daysToRestore) {
            $existing = DB::table('comp_off_leaves')
                ->where('employees_id', $employeeId)
                ->where('comp_off_date', $currentDate->format('Y-m-d'))
                ->exists();

            if (!$existing) {
                DB::table('comp_off_leaves')->insert([
                    'employees_id' => $employeeId,
                    'comp_off_date' => $currentDate->format('Y-m-d'),
                    'comp_off_data' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $restoredCount++;
            }

            $currentDate->modify('+1 day');
        }

        if ($restoredCount < $daysToRestore) {
            $remainingDays = $daysToRestore - $restoredCount;
            $futureDate = clone $end;
            $futureDate->modify('+1 day');

            for ($i = 0; $i < $remainingDays; $i++) {
                DB::table('comp_off_leaves')->insert([
                    'employees_id' => $employeeId,
                    'comp_off_date' => $futureDate->format('Y-m-d'),
                    'comp_off_data' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $futureDate->modify('+1 day');
            }
        }
    }

    /**
     * Reduce used_days on monthly balance (mirrors approval deduction for Earned/Sick etc.).
     */
    public static function restoreRegularLeaveBalance(int $employeeId, int $leaveTypeId, float $totalDays): void
    {
        $now = now();
        $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if ($balance) {
            $oldUsedDays = $balance->used_days;
            $balance->used_days = max(0, $balance->used_days - $totalDays);
            $balance->save();

            Log::info('Restored leave balance', [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'total_days' => $totalDays,
                'old_used_days' => $oldUsedDays,
                'new_used_days' => $balance->used_days,
                'allocated_days' => $balance->allocated_days,
            ]);
        } else {
            $leaveType = LeaveType::find($leaveTypeId);
            if ($leaveType) {
                $defaultAllocation = 0;
                
                $employee = Employee::find($employeeId);
                $isEligible = true;
                if ($employee && !empty($employee->company_doj)) {
                    $eligibilityDate = \Carbon\Carbon::parse($employee->company_doj)->addDays(30);
                    if ($now->lt($eligibilityDate)) {
                        $isEligible = false;
                    }
                }
                
                if ($isEligible) {
                    if ($leaveType->title === 'Earned Leave') {
                        $defaultAllocation = 1.5;
                    } elseif ($leaveType->title === 'Sick Leave') {
                        $defaultAllocation = 0.0;
                    }
                }

                EmployeeLeaveBalance::create([
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'year' => $now->year,
                    'month' => $now->month,
                    'allocated_days' => $defaultAllocation,
                    'used_days' => 0,
                    'carry_forward_days' => 0,
                ]);

                Log::info('Created balance record during restore', [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'allocated_days' => $defaultAllocation,
                ]);
            } else {
                Log::warning('Attempted to restore balance for non-existent balance record and leave type', [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'total_days' => $totalDays,
                    'year' => $now->year,
                    'month' => $now->month,
                ]);
            }
        }
    }

    /**
     * Increase used_days when an approved leave segment is created outside the normal approval UI.
     */
    public static function applyRegularLeaveBalanceDeduction(int $employeeId, int $leaveTypeId, float $totalDays): void
    {
        $now = now();
        $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if ($balance) {
            $balance->used_days += $totalDays;
            $balance->save();
        } else {
            $leaveType = LeaveType::find($leaveTypeId);
            if (!$leaveType) {
                return;
            }
            $defaultAllocation = 0;
            
            $employee = Employee::find($employeeId);
            $isEligible = true;
            if ($employee && !empty($employee->company_doj)) {
                $eligibilityDate = \Carbon\Carbon::parse($employee->company_doj)->addDays(30);
                if ($now->lt($eligibilityDate)) {
                    $isEligible = false;
                }
            }
            
            if ($isEligible) {
                if ($leaveType->title === 'Earned Leave') {
                    $defaultAllocation = 1.5;
                } elseif ($leaveType->title === 'Sick Leave') {
                    $defaultAllocation = 0.0;
                }
            }

            EmployeeLeaveBalance::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $now->year,
                'month' => $now->month,
                'allocated_days' => $defaultAllocation,
                'used_days' => $totalDays,
                'carry_forward_days' => 0,
            ]);
        }
    }
}
