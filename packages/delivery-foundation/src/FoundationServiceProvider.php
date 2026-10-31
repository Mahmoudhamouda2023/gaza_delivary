<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation;

use GazaDelivery\Foundation\Console\BootstrapSuperAdmin;
use GazaDelivery\Foundation\Http\Middleware\EnsureAccountIsActive;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Policies\StorePolicy;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/delivery-foundation.php', 'delivery-foundation');
    }

    public function boot(Router $router): void
    {
        // Add named guards; never overwrite the host application's default web guard.
        config([
            'auth.guards.staff' => ['driver' => 'session', 'provider' => 'delivery_staff'],
            'auth.guards.merchant' => ['driver' => 'session', 'provider' => 'delivery_merchants'],
            'auth.providers.delivery_staff' => ['driver' => 'eloquent', 'model' => Staff::class],
            'auth.providers.delivery_merchants' => ['driver' => 'eloquent', 'model' => User::class],
        ]);

        Gate::policy(Store::class, StorePolicy::class);
        $router->aliasMiddleware('delivery.active', EnsureAccountIsActive::class);
        if (config('delivery.load_foundation_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
        $this->publishes([__DIR__.'/../config/delivery-foundation.php' => config_path('delivery-foundation.php')], 'delivery-foundation-config');

        if ($this->app->runningInConsole()) {
            $this->commands([BootstrapSuperAdmin::class]);
        }
    }
}
