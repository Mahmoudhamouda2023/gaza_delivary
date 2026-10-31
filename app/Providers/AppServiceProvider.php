<?php

namespace App\Providers;

use GazaDelivery\Foundation\Models\Staff;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
        Gate::define('access-admin', fn (Staff $staff) => $staff->isActive() && $staff->hasAnyRole(['super_admin', 'operations', 'finance']));
        Gate::define('access-captain', fn (Staff $staff) => $staff->isActive() && $staff->hasRole('captain'));
        Gate::define('manage-staff', fn (Staff $staff) => $staff->isActive() && $staff->hasRole('super_admin'));
        Gate::define('operate-delivery', fn (Staff $s) => $s->isActive() && $s->hasAnyRole(['super_admin', 'operations']));
        Gate::define('configure-delivery', fn (Staff $s) => $s->isActive() && $s->hasAnyRole(['super_admin', 'finance']));
        Gate::define('finance-delivery', fn (Staff $s) => $s->isActive() && $s->hasAnyRole(['super_admin', 'finance']));
        RateLimiter::for('login-ip', fn (Request $r) => Limit::perMinute(30)->by('login-ip:'.$r->ip()));
        RateLimiter::for('registration', fn (Request $r) => Limit::perHour(10)->by('registration:'.$r->ip()));
        RateLimiter::for('password-change', fn (Request $r) => Limit::perMinute(5)->by('password:'.$r->ip().'|'.$r->user()?->getAuthIdentifier()));
        RateLimiter::for('admin-write', fn (Request $r) => Limit::perMinute(30)->by('admin-write:'.$r->user('staff')?->getKey()));
    }
}
