<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$config = config('menu_permissions');
$count = 0;
foreach ($config as $module => $submodules) {
    foreach ($submodules as $submodule => $groups) {
        foreach ($groups as $group => $data) {
            if (isset($data['actions'])) {
                foreach ($data['actions'] as $actionName => $permissionName) {
                    $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                    if ($perm->wasRecentlyCreated) {
                        echo "Created: $permissionName\n";
                        $count++;
                    }
                }
            }
        }
    }
}
echo "Finished syncing. Created $count new permissions.\n";
