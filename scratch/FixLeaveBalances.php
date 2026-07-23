<?php
// FixLeaveBalances.php
// This script retrospectively allocates monthly leaves from January to the current month.

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveBalance;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function allocateForMonth($year, $month) {
    echo "Processing Month: $year-$month\n";
    $employees = Employee::whereNotNull('created_by')->get();
    $now = Carbon::create($year, $month, 1);

    foreach ($employees as $employee) {
        $earnedLeaveType = getOrCreateLeaveTypeForCompany('Earned Leave', 1.0, $employee->created_by);
        $sickLeaveType = getOrCreateLeaveTypeForCompany('Sick Leave', 1.0, $employee->created_by);

        allocateLeaveForEmployee($employee, $earnedLeaveType, 1.0, $year, $month, $now);
        allocateLeaveForEmployee($employee, $sickLeaveType, 1.0, $year, $month, $now);
    }
}

function getOrCreateLeaveTypeForCompany($title, $defaultDays, $creatorId) {
    $leaveType = LeaveType::where('title', $title)
        ->where('created_by', $creatorId)
        ->first();
    if (!$leaveType) {
        $leaveType = LeaveType::create([
            'title' => $title,
            'days' => $defaultDays,
            'created_by' => $creatorId,
        ]);
    }
    return $leaveType;
}

function allocateLeaveForEmployee($employee, $leaveType, $allocatedDays, $year, $month, $now) {
    $previousMonth = $now->copy()->subMonth();
    $prevBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $previousMonth->year)
        ->where('month', $previousMonth->month)
        ->first();

    $carryForward = 0;
    if ($prevBalance) {
        $availableDays = ($prevBalance->allocated_days + $prevBalance->carry_forward_days) - $prevBalance->used_days;
        $carryForward = max(0, $availableDays);
    }

    $existingBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $year)
        ->where('month', $month)
        ->first();

    if ($existingBalance) {
        $existingBalance->allocated_days = $allocatedDays;
        $existingBalance->carry_forward_days = $carryForward;
        $existingBalance->save();
    } else {
        EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
            'month' => $month,
            'allocated_days' => $allocatedDays,
            'used_days' => 0,
            'carry_forward_days' => $carryForward
        ]);
    }
}

// Run for 2026: Jan to May
$currentMonth = (int)date('m');
for ($m = 1; $m <= $currentMonth; $m++) {
    allocateForMonth(2026, $m);
}

echo "\nDone! All leave balances have been updated retrospectively.\n";
