<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;

$e = Employee::find(1);
$earned = DB::table('comp_off_leaves')->where('employees_id', 1)->count();
$used = Leave::where('employee_id', 1)->where('leave_type_id', 4)->where('status', 'Approved')->sum('total_leave_days');
$balance = $e->compOffBalance();

echo "Employee: {$e->full_name}\n";
echo "Earned: $earned\n";
echo "Used: $used\n";
echo "Balance: $balance\n";
