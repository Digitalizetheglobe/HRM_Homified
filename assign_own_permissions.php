<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

$excluded = [
    'attendance.bulk.view.own',
    'attendance.biometric.view.own',
    'leave.details.view.own',
    'payroll.payable_days.view.own',
];

$permissions = Permission::where('name', 'like', '%.own')->pluck('name')->toArray();
$permissionsToAssign = array_diff($permissions, $excluded);

$users = User::where('type', 'employee')->get();
$count = 0;
foreach ($users as $user) {
    // Sync the filtered "own" permissions, plus whatever they already had
    $user->givePermissionTo($permissionsToAssign);
    $count++;
}
echo "Assigned default own permissions to $count employees.";
