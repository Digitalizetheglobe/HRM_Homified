<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Employee;

$user = User::where('name', 'LIKE', '%Ganesh%')->first();
if ($user) {
    echo "User ID: " . $user->id . "\n";
    $employee = Employee::where('user_id', $user->id)->first();
    if ($employee) {
        echo "Employee ID: " . $employee->id . "\n";
        echo "Status: '" . $employee->approval_status . "'\n";
    } else {
        echo "No employee record found.\n";
    }
} else {
    echo "User not found.\n";
}
