<?php

use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/about', 'public.about')->name('public.about');
Route::view('/captains/join', 'public.captains')->name('public.captains');
Route::view('/terms', 'public.terms')->name('public.terms');
Route::middleware('guest:merchant,staff')->group(function (): void {
    Route::get('/login', [SessionController::class, 'merchantCreate'])->name('login');
    Route::post('/login', [SessionController::class, 'merchantStore'])->middleware('throttle:login-ip')->name('merchant.login.store');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:registration')->name('register.store');
    Route::get('/staff/login', [SessionController::class, 'staffCreate'])->name('staff.login');
    Route::post('/staff/login', [SessionController::class, 'staffStore'])->middleware('throttle:login-ip')->name('staff.login.store');
});
Route::post('/logout', [SessionController::class, 'merchantDestroy'])->middleware('auth:merchant')->name('merchant.logout');
Route::post('/staff/logout', [SessionController::class, 'staffDestroy'])->middleware('auth:staff')->name('staff.logout');
Route::middleware(['auth:merchant', 'identity:merchant'])->group(function (): void {
    Route::get('/account/status', [DashboardController::class, 'status'])->name('merchant.status');
    Route::get('/account/password', [PasswordController::class, 'merchantEdit'])->name('merchant.password.edit');
    Route::put('/account/password', [PasswordController::class, 'merchantUpdate'])->middleware('throttle:password-change')->name('merchant.password.update');
    Route::get('/merchant', [DashboardController::class, 'merchant'])->middleware(['password.changed:merchant', 'approved'])->name('merchant.dashboard');
});
Route::middleware(['auth:staff', 'identity:staff'])->group(function (): void {
    Route::get('/staff/password', [PasswordController::class, 'staffEdit'])->name('staff.password.edit');
    Route::put('/staff/password', [PasswordController::class, 'staffUpdate'])->middleware('throttle:password-change')->name('staff.password.update');
    Route::middleware('password.changed:staff')->group(function (): void {
        Route::get('/captain', [DashboardController::class, 'captain'])->middleware('can:access-captain')->name('captain.dashboard');
        Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function (): void {
            Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
            Route::get('/stores/data', [StoreController::class, 'data'])->middleware('can:stores.view')->name('stores.data');
            Route::get('/stores', [StoreController::class, 'index'])->middleware('can:stores.view')->name('stores.index');
            Route::get('/stores/{publicId}', [StoreController::class, 'show'])->middleware('can:stores.view')->name('stores.show');
            Route::patch('/stores/{publicId}', [StoreController::class, 'update'])->middleware('throttle:admin-write')->name('stores.update');
            Route::get('/staff', [StaffController::class, 'index'])->middleware('can:staff.view')->name('staff.index');
            Route::get('/staff/create', [StaffController::class, 'create'])->middleware('can:manage-staff')->name('staff.create');
            Route::post('/staff', [StaffController::class, 'store'])->middleware(['can:manage-staff', 'throttle:admin-write'])->name('staff.store');
            Route::patch('/staff/{staff}/suspend', [StaffController::class, 'suspend'])->middleware(['can:manage-staff', 'throttle:admin-write'])->name('staff.suspend');
        });
    });
});
