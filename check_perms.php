<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(13);
if ($user) {
    echo "User found: " . $user->name . "\n";
    echo "Type: " . $user->type . "\n";
    echo "Permissions:\n";
    print_r($user->getAllPermissions()->pluck('name')->toArray());
} else {
    echo "User 13 not found.\n";
}
