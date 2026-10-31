<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\IdentityAuthentication;
use Closure;
use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class IdentitySession
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        abort_unless(in_array($guard, ['staff', 'merchant'], true), 500);
        $account = Auth::guard($guard)->user();
        $correctType = $guard === 'staff' ? $account instanceof Staff : $account instanceof User;
        $account = $correctType ? $account->fresh() : null;
        $valid = $account && $account->status !== AccountStatus::Suspended
            && (int) $request->session()->get('delivery_auth.'.$guard.'.version', 0) === $account->session_version;

        if (! $valid) {
            app(IdentityAuthentication::class)->logout($request);
            if ($request->expectsJson()) {
                abort(401, 'انتهت الجلسة. سجّل الدخول مجددًا.');
            }

            return redirect()->route($guard === 'staff' ? 'staff.login' : 'login')
                ->with('status', 'انتهت الجلسة أو تغيّرت حالة الحساب. سجّل الدخول مجددًا.');
        }

        Auth::guard($guard)->setUser($account);
        Auth::shouldUse($guard);

        return $next($request);
    }
}
