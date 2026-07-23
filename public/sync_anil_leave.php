<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find Anil
$employee = \App\Models\Employee::where('name', 'like', '%Anil Shivaji Ambhure%')->first();

if (!$employee) {
    die("Employee not found.\n");
}

echo "Found Employee: {$employee->name} (ID: {$employee->id})\n";

// Get all balances for Anil
$balances = \App\Models\EmployeeLeaveBalance::where('employee_id', $employee->id)->get();
$fixedCount = 0;

foreach ($balances as $balance) {
    // Calculate actual used days from Approved leaves
    $actualUsed = \App\Models\Leave::where('employee_id', $balance->employee_id)
        ->where('leave_type_id', $balance->leave_type_id)
        ->where('status', 'Approved')
        ->whereYear('start_date', $balance->year)
        ->whereMonth('start_date', $balance->month)
        ->sum('total_leave_days');
        
    if ($balance->used_days != $actualUsed) {
        echo "Leave Type ID: {$balance->leave_type_id}, Year: {$balance->year}, Month: {$balance->month}\n";
        echo "  - Changing Used Days from {$balance->used_days} to {$actualUsed}\n";
        $balance->used_days = $actualUsed;
        $balance->save();
        $fixedCount++;
    }
}

echo "Fixed {$fixedCount} balance records for Anil.\n";
