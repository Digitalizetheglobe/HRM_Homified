<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "Refactoring Permissions...\n";

// Get roles
$employeeRole = Role::firstOrCreate(['name' => 'employee']);
$companyRole = Role::firstOrCreate(['name' => 'company']);
$hrRole = Role::firstOrCreate(['name' => 'hr']);

// Load config
$config = config('menu_permissions');

$allPermsToCreate = [];
$ownPerms = [];

foreach ($config as $moduleName => $features) {
    foreach ($features as $featureName => $scopes) {
        foreach ($scopes as $scopeName => $scopeConfig) {
            foreach ($scopeConfig['actions'] as $actionName => $permString) {
                $allPermsToCreate[] = $permString;
                if (str_contains($permString, '.own')) {
                    $ownPerms[] = $permString;
                }
            }
        }
    }
}

// Create permissions
foreach ($allPermsToCreate as $permString) {
    Permission::firstOrCreate(['name' => $permString, 'guard_name' => 'web']);
}

// Revoke legacy permissions from employee role
// Basically, we only want the employee to have `.own` permissions and nothing else
// So we sync permissions to only the `.own` perms.
echo "Syncing permissions for employee role...\n";
$employeeRole->syncPermissions($ownPerms);

// Grant everything to company and HR so they don't lose access
echo "Syncing permissions for company & hr roles...\n";
$allPerms = Permission::all();
$companyRole->syncPermissions($allPerms);
$hrRole->syncPermissions($allPerms);

echo "Permissions Refactoring Completed Successfully.\n";
