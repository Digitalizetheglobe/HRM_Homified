<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DynamicMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('dynamic_menus')->truncate();

        $menus = [
            // Dashboard
            [
                'title' => 'Dashboard',
                'route_name' => 'dashboard',
                'icon' => 'ti ti-home',
                'permission_required' => null, // Everyone with access to system sees this
                'order' => 10,
                'children' => []
            ],
            
            // Employee Module
            [
                'title' => 'Employee',
                'route_name' => 'employee.index', // Usually the main list
                'icon' => 'ti ti-users',
                'permission_required' => 'employee.profile.view.own', // Base requirement to see the parent menu
                'order' => 20,
                'children' => [
                    [
                        'title' => 'My Profile',
                        'route_name' => 'employee.show', // Requires ID parameter in practice, usually handled via auth()->id in controller
                        'icon' => '',
                        'permission_required' => 'employee.profile.view.own',
                        'order' => 10,
                    ],
                    [
                        'title' => 'All Employees',
                        'route_name' => 'employee.index',
                        'icon' => '',
                        'permission_required' => 'employee.profile.view.company',
                        'order' => 20,
                    ],
                ]
            ],
            
            // Leave Module
            [
                'title' => 'Leave',
                'route_name' => null,
                'icon' => 'ti ti-calendar',
                'permission_required' => 'leave.requests.view.own', // If they can't even see own leaves, hide the whole module
                'order' => 30,
                'children' => [
                    [
                        'title' => 'My Leaves',
                        'route_name' => 'leave.index',
                        'icon' => '',
                        'permission_required' => 'leave.requests.view.own',
                        'order' => 10,
                    ],
                    [
                        'title' => 'All Leaves',
                        'route_name' => 'leave.all',
                        'icon' => '',
                        'permission_required' => 'leave.requests.view.company',
                        'order' => 20,
                    ],
                    [
                        'title' => 'Leave Approval',
                        'route_name' => 'leave.approval.index',
                        'icon' => '',
                        'permission_required' => 'leave.approval.view.department',
                        'order' => 30,
                    ],
                ]
            ],
            
            // Roles and Permissions
            [
                'title' => 'Roles & Permissions',
                'route_name' => 'roles.index',
                'icon' => 'ti ti-key',
                'permission_required' => 'setup.hrm.edit.company', // Assuming super admins or company owners have this
                'order' => 90,
                'children' => []
            ]
        ];

        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);
            
            $parent = \App\Models\DynamicMenu::create($menuData);
            
            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                \App\Models\DynamicMenu::create($childData);
            }
        }
    }
}
