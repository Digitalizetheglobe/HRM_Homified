<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Leave;
use App\Models\AttendanceEmployee;
use App\Models\CompOffLeave;

echo "Leave:\n";
$leaves = Leave::where('employee_id', 188)->where('start_date', 'like', '2026-06-25%')->get();
foreach($leaves as $l) {
    echo "ID: $l->id, TypeID: $l->leave_type_id\n";
}

echo "Attendance:\n";
$att = AttendanceEmployee::where('employee_id', 188)->where('date', '2026-06-25')->first();
if($att) echo "Status: $att->status\n";

echo "CompOffEarned:\n";
$coff = CompOffLeave::where('employees_id', 188)->where('comp_off_date', '2026-06-25')->first();
if($coff) echo "Earned\n";
