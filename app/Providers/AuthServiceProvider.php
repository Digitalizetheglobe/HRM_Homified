<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->type == 'company') {
                return true;
            }

            // Automatically grant 'Own' permissions to employees (excluding specific admin/management features)
            if ($user->type === 'employee' && \Illuminate\Support\Str::endsWith($ability, '.own')) {
                $excludedOwnPermissions = [
                    'attendance.bulk.view.own',
                    'attendance.biometric.view.own',
                    'leave.details.view.own',
                    'payroll.payable_days.view.own',
                    'exit.resignation.view.own',
                    'exit.resignation.show.own',
                    'exit.resignation.create.own',
                    'exit.resignation.edit.own',
                    'exit.resignation.delete.own',
                    'exit.termination.view.own',
                    'exit.termination.create.own',
                    'exit.termination.edit.own',
                    'exit.termination.delete.own',
                    'exit.termination.show.own'
                ];
                if (in_array($ability, $excludedOwnPermissions)) {
                    return null; // Let it fall back to explicit permission checks if any
                }
                return true;
            }
        });
    }
}
