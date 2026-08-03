<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckHrmSetupPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user) {
            $routeName = $request->route() ? $request->route()->getName() : null;
            if ($routeName) {
                $setupPrefixes = [
                    'branch',
                    'site',
                    'department',
                    'designation',
                    'leavetype',
                    'document',
                    'paysliptype',
                    'allowanceoption',
                    'loanoption',
                    'deductionoption',
                    'goaltype',
                    'trainingtype',
                    'awardtype',
                    'terminationtype',
                    'job-category',
                    'job-stage',
                    'performanceType',
                    'competencies',
                    'expensetype',
                    'incometype',
                    'paymenttype',
                    'contract_type',
                ];

                foreach ($setupPrefixes as $prefix) {
                    if (str_starts_with($routeName, $prefix . '.')) {
                        // This is an HRM System Setup route
                        if ($user->type === 'employee') {
                            // Determine required permission based on action
                            $action = 'view';
                            if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.store')) {
                                $action = 'create';
                            } elseif (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.update')) {
                                $action = 'edit';
                            } elseif (str_ends_with($routeName, '.destroy')) {
                                $action = 'delete';
                            }

                            $permission = "setup.hrm.{$action}.all";
                            if (!$user->can($permission)) {
                                if ($request->expectsJson() || $request->ajax()) {
                                    return response()->json(['error' => __('Permission denied.')], 403);
                                }
                                abort(403, 'Permission denied.');
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $next($request);
    }
}
