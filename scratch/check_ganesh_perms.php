<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('name', 'LIKE', '%Ganesh%')->first();
if ($user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
    echo "Type: " . $user->type . "\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    echo "Permissions count: " . $user->getAllPermissions()->count() . "\n";
    
    $checkPerms = ['Manage Attendance', 'Manage Employee', 'Manage Leave', 'Manage Company Policy'];
    foreach ($checkPerms as $perm) {
        echo "Can $perm? " . ($user->can($perm) ? 'YES' : 'NO') . "\n";
    }
}
