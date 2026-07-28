<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Employee;

class CheckEmployeeApproval
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->type == 'employee') {
            $employee = Employee::where('user_id', Auth::id())->first();

            if ($employee && strtolower(trim($employee->approval_status ?? '')) !== 'approved') {
                $allowedRoutes = [
                    'employee.show',
                    'employee.edit',
                    'employee.update',
                    'logout',
                    'company-policy.employee.preview',
                    'company-policy.employee.download',
                    'company-policy.employee.stream',
                    'company-policy.employee.track-download',
                    'company-policy.employee.acknowledge',
                    'company.logo',
                    'storage.proxy',
                ];

                $currentRoute = $request->route() ? $request->route()->getName() : null;

                if (!in_array($currentRoute, $allowedRoutes)) {
                    $status = strtolower(trim($employee->approval_status ?? 'pending'));
                    $msg = ($status === 'rejected')
                        ? __('Your account has been rejected. You only have access to your profile.')
                        : __('Your account is pending approval. You only have access to your profile.');

                    return redirect()->route('employee.show', Crypt::encrypt($employee->id))
                        ->with('warning', $msg);
                }
            }
        }

        return $next($request);
    }
}
