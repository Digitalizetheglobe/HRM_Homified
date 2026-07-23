<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$employeeRole = Role::where('name', 'employee')->first();
if ($employeeRole) {
    $employeePermission = [
        ["name" => "Manage Award"],
        ["name" => "Manage Transfer"],
        ["name" => "Manage Resignation"],
        ["name" => "Create Resignation"],
        ["name" => "Edit Resignation"],
        ["name" => "Delete Resignation"],
        ["name" => "Manage Travel"],
        ["name" => "Manage Promotion"],
        ["name" => "Manage Complaint"],
        ["name" => "Create Complaint"],
        ["name" => "Edit Complaint"],
        ["name" => "Delete Complaint"],
        ["name" => "Manage Warning"],
        ["name" => "Create Warning"],
        ["name" => "Edit Warning"],
        ["name" => "Delete Warning"],
        ["name" => "Manage Termination"],
        ["name" => "Manage Employee"],
        ["name" => "Edit Employee"],
        ["name" => "Show Employee"],
        ["name" => "Manage Allowance"],
        ["name" => "Manage Event"],
        ["name" => "Manage Announcement"],
        ["name" => "Manage Leave"],
        ["name" => "Create Leave"],
        ["name" => "Edit Leave"],
        ["name" => "Delete Leave"],
        ["name" => "Manage Meeting"],
        ["name" => "Manage Ticket"],
        ["name" => "Create Ticket"],
        ["name" => "Edit Ticket"],
        ["name" => "Delete Ticket"],
        ["name" => "Manage Language"],
        ["name" => "Manage Enquiry"],
        ["name" => "Create Enquiry"],
        ["name" => "Edit Enquiry"],
        ["name" => "Delete Enquiry"],
        ["name" => "Manage TimeSheet"],
        ["name" => "Create TimeSheet"],
        ["name" => "Edit TimeSheet"],
        ["name" => "Delete TimeSheet"],
        ["name" => "View TimeSheet"],
        ["name" => "Manage Attendance"],
        ["name" => 'Manage Document'],
        ["name" => "Manage Holiday"],
        ["name" => "Manage Career"],
        ["name" => "Manage Contract"],
        ["name" => "Store Note"],
        ["name" => "Delete Note"],
        ["name" => "Store Comment"],
        ["name" => "Delete Comment"],
        ["name" => "Delete Attachment"],
        ["name" => "Manage Zoom meeting"],
        ["name" => "Show Zoom meeting"],
    ];

    foreach ($employeePermission as $perm) {
        Permission::firstOrCreate(['name' => $perm['name']]);
    }
    
    $employeeRole->givePermissionTo($employeePermission);
    echo "Permissions restored successfully.";
} else {
    echo "Employee role not found.";
}
