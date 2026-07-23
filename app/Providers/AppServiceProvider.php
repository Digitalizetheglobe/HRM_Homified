<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\AttendanceEmployee;
use App\Models\Employee;
use App\Observers\AttendanceEmployeeObserver;
use App\Services\TwilioService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TwilioService::class, function ($app) {
            return new TwilioService();
        });  
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        AttendanceEmployee::observe(AttendanceEmployeeObserver::class);

        View::composer('partial.Admin.menu', function ($view) {
            if (auth()->check() && auth()->user()->type == 'employee') {
                $employee = Employee::where('user_id', auth()->id())->first();
                $view->with('employee', $employee);
            }
        });
    }
}
