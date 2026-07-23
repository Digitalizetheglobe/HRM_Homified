<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::enableQueryLog();

$leave_id = 150;
$leave = DB::table('leaves')->where('id', $leave_id)->first();
$total_leave_days = $leave->total_leave_days;
$leaveType = DB::table('leave_types')->where('id', $leave->leave_type_id)->first();
$isCompOff = $leaveType && $leaveType->title === 'Comp-Off';

echo "isCompOff: " . ($isCompOff ? 'YES' : 'NO') . "\n";
echo "total_leave_days: " . $total_leave_days . "\n";

if ($isCompOff) {
    $daysToDeduct = (int)$total_leave_days;
    echo "daysToDeduct: " . $daysToDeduct . "\n";
    
    $availableCompOff = DB::table('comp_off_leaves')
        ->where('employees_id', $leave->employee_id)
        ->count();
    echo "availableCompOff: " . $availableCompOff . "\n";

    // Simulate the deletion without actually deleting (or we can use a transaction)
    DB::beginTransaction();
    
    $query = DB::table('comp_off_leaves')
        ->where('employees_id', $leave->employee_id)
        ->orderBy('comp_off_date', 'asc')
        ->limit($daysToDeduct);
        
    $countBefore = DB::table('comp_off_leaves')->where('employees_id', $leave->employee_id)->count();
    $query->delete();
    $countAfter = DB::table('comp_off_leaves')->where('employees_id', $leave->employee_id)->count();
    
    echo "Count Before: " . $countBefore . "\n";
    echo "Count After: " . $countAfter . "\n";
    echo "Queries executed:\n";
    print_r(DB::getQueryLog());
    
    DB::rollBack();
}
