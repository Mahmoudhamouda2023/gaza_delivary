<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Http\Middleware;

use Closure;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        abort_unless(in_array($guard, ['staff', 'merchant'], true), 500, 'Invalid identity guard.');
        $auth = Auth::guard($guard);
        $account = $auth->user();
        $key = 'delivery_auth.'.$guard.'.version';
        $typeMatches = $guard === 'staff' ? $account instanceof Staff : $account instanceof User;

        if (! $typeMatches) {
            abort(401);
        }

        // A fresh DB lookup makes suspension effective on the very next protected request.
        $account = $account->fresh();
        if (! $account || ! $account->isActive()
            || (int) $request->session()->get($key, 0) !== $account->session_version) {
            $auth->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(401, 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجددًا.');
        }

        return $next($request);
    }
}
