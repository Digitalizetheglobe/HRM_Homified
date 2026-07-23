<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasDataScope
{
    /**
     * Scope a query to only include records accessible by the user's permissions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplyAccessControl($query, $module)
    {
        $user = Auth::user();

        // Company-level access (sees everything)
        if ($user->can("{$module}.view.company")) {
            return $query;
        }

        // Department-level access
        if ($user->can("{$module}.view.department")) {
            // Assumes user is an employee with a department_id
            $departmentId = $user->employee ? $user->employee->department_id : null;
            return $query->where('department_id', $departmentId);
        }

        // Own data access
        if ($user->can("{$module}.view.own")) {
            // Assumes the model has an employee_id or user_id column
            if (\Schema::hasColumn($this->getTable(), 'employee_id')) {
                $employeeId = $user->employee ? $user->employee->id : null;
                return $query->where('employee_id', $employeeId);
            }
            
            return $query->where('user_id', $user->id);
        }

        // Failsafe: If no scope permissions match, return empty query
        return $query->whereRaw('1 = 0');
    }
}
