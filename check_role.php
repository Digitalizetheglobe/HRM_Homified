<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;

$role = Role::where('name', 'Employee')->first();
if ($role) {
    echo "Employee Role permissions:\n";
    foreach ($role->permissions as $p) {
        echo "- " . $p->name . "\n";
    }
} else {
    echo "Employee role not found.";
}
