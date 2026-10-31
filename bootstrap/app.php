<?php

use App\Http\Middleware\ApprovedMerchant;
use App\Http\Middleware\IdentitySession;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias([
            'identity' => IdentitySession::class,
            'approved' => ApprovedMerchant::class,
            'password.changed' => RequirePasswordChange::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('staff', 'staff/*', 'admin', 'admin/*', 'captain', 'captain/*') ? route('staff.login') : route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);
    })->create();
