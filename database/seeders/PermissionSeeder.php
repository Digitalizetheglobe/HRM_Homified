<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the exact naming convention for long-term scalability
        // Format: {module}.{page}.{action}.{scope/workflow}
        
        $permissions = [
            // --- EMPLOYEE MODULE ---
            'employee.profile.view.own',
            'employee.profile.view.company',
            'employee.profile.edit.own',
            'employee.profile.edit.company',
            'employee.permissions.view.company',
            'employee.permissions.edit.company',

            // --- LEAVE MODULE ---
            'leave.requests.view.own',
            'leave.requests.view.department',
            'leave.requests.view.company',
            'leave.requests.create',
            'leave.requests.edit.own',
            'leave.requests.delete.own',
            
            // Workflow specific
            'leave.approval.view.department',
            'leave.approval.view.company',
            'leave.approval.approve',
            'leave.approval.reject',
            
            'leave.reports.view.company',
            'leave.reports.export.company',

            // --- ATTENDANCE MODULE ---
            'attendance.calendar.view.own',
            'attendance.calendar.view.department',
            'attendance.calendar.view.company',
            
            'attendance.regularisation.view.own',
            'attendance.regularisation.create',
            'attendance.regularisation.approve',
            
            'attendance.records.view.own',
            'attendance.records.view.company',
            'attendance.records.edit.company',

            // --- PAYROLL MODULE ---
            'payroll.salary.view.own',
            'payroll.salary.view.company',
            'payroll.salary.edit.company',
            
            'payroll.payslip.view.own',
            'payroll.payslip.view.company',
            'payroll.payslip.generate.company',
            
            'payroll.processing.view.company',
            'payroll.processing.initiate.company',
            'payroll.processing.verify.company',
            'payroll.processing.approve.company',

            // --- TICKET MODULE ---
            'ticket.requests.view.own',
            'ticket.requests.view.company',
            'ticket.requests.create',
            'ticket.requests.edit.own',
            'ticket.requests.edit.company',
            'ticket.manage.view.own',
            
            // --- EXIT FORMALITIES ---
            'exit.resignation.view.own',
            'exit.resignation.view.all',
            'exit.termination.view.own',
            'exit.termination.view.all',
            
            // --- MISC / OTHERS ---
            'attendance.marked.view.own',
            'attendance.bulk.view.own',
            'attendance.biometric.view.own',
            'leave.manage.view.own',
            'leave.details.view.own',
            'payroll.payable_days.view.own',
            'company_policy.manage.view.own',
            
            // --- CONSTANTS / SYSTEM SETUP ---
            'setup.hrm.view.company',
            'setup.hrm.edit.company',
        ];

        // Seed the permissions
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Example Roles
        $companyRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'company']);
        $employeeRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'employee']);
        $hrRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hr_manager']);
        
        // Note: For actual deployment, the UI will assign these, but we can assign some defaults
        $employeePermissions = collect($permissions)->filter(function ($perm) {
            return str_ends_with($perm, '.own') || str_ends_with($perm, '.create');
        })->toArray();
        $employeeRole->givePermissionTo($employeePermissions);
    }
}
