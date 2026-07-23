<?php
$permissions = [
    'exit.resignation.view.own',
    'exit.resignation.show.own',
    'exit.resignation.create.own',
    'exit.resignation.edit.own',
    'exit.resignation.delete.own'
];
foreach($permissions as $p) {
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p]);
}
$users = \App\Models\User::where('type', 'employee')->get();
foreach($users as $user) {
    $user->givePermissionTo($permissions);
}
echo "Granted successfully";
