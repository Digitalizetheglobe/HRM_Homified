<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class EmployeePermissionController extends Controller
{
    public function index()
    {
        if (\Auth::user()->type != 'company' && \Auth::user()->type != 'super admin') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Get all employees for the dropdown
        $employees = User::where('type', 'employee')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->get();

        // Load the new Menu-Driven Permission mapping
        $menuPermissions = config('menu_permissions', []);

        return view('employee-permissions.index', compact('employees', 'menuPermissions'));
    }

    public function fetch(Request $request)
    {
        if (\Auth::user()->type != 'company' && \Auth::user()->type != 'super admin') {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['error' => __('User not found.')], 404);
        }

        // Get direct permissions assigned to the user
        $permissions = $user->getDirectPermissions()->pluck('name');

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    public function sync(Request $request)
    {
        if (\Auth::user()->type != 'company' && \Auth::user()->type != 'super admin') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array'
        ]);

        $user = User::find($request->user_id);
        
        // Ensure all submitted permissions exist in the database
        $permissions = $request->permissions ?? [];
        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Sync the direct permissions
        $user->syncPermissions($permissions);

        return redirect()->back()->with('success', __('Permissions updated successfully.'))->with('selected_user_id', $request->user_id);
    }
}
