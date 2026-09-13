<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TimeEntry;
use App\Policies\TimeEntryPolicy;
use Illuminate\Support\Facades\Gate;
use App\Models\Payroll;
use App\Policies\PayrollPolicy;
use App\Models\User;
use App\Policies\UserPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(TimeEntry::class, TimeEntryPolicy::class);
        Gate::policy(Payroll::class, PayrollPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
