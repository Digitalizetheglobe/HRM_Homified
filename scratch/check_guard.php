<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('name', 'LIKE', '%Ganesh%')->first();
if ($user) {
    echo "User: " . $user->name . "\n";
    $permissions = $user->getAllPermissions();
    if ($permissions->count() > 0) {
        $p = $permissions->first();
        echo "Example Permission: " . $p->name . " (Guard: " . $p->guard_name . ")\n";
    } else {
        echo "No permissions found.\n";
    }
}
