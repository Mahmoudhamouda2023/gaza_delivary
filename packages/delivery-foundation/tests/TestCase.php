<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Tests;

use GazaDelivery\Foundation\Database\Seeders\AccessControlSeeder;
use GazaDelivery\Foundation\FoundationServiceProvider;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Services\MerchantRegistration;
use GazaDelivery\Foundation\Services\StoreDirectory;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PermissionServiceProvider::class, FoundationServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('hashing.bcrypt.rounds', 4);
    }

    protected function defineDatabaseMigrations(): void
    {
        $migration = require __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $migration->up();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->seed(AccessControlSeeder::class);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware(['web', 'auth:staff', 'delivery.active:staff'])->get('/test/staff', fn () => response()->json(['ok' => true]));
        Route::middleware(['web', 'auth:merchant', 'delivery.active:merchant'])->get('/test/merchant', fn () => response()->json(['ok' => true]));
        Route::middleware(['web', 'auth:merchant'])->get('/test/stores', function (StoreDirectory $directory) {
            return $directory->queryFor(auth('merchant')->user())->get(['public_id', 'name']);
        });
    }

    protected function staff(string $role, string $phone = '0599999999'): Staff
    {
        $staff = new Staff;
        $staff->forceFill([
            'name' => 'Test Staff', 'phone' => $phone, 'password' => 'StaffPassword123!',
            'status' => 'active', 'must_change_password' => false,
        ])->save();
        $staff->assignRole($role);

        return $staff;
    }

    protected function registration(array $overrides = []): User
    {
        return app(MerchantRegistration::class)->register(array_merge([
            'name' => 'تاجر تجريبي', 'phone' => '0591234567', 'store_name' => 'متجر تجريبي',
            'password' => 'MerchantPassword123!', 'password_confirmation' => 'MerchantPassword123!',
        ], $overrides));
    }
}
