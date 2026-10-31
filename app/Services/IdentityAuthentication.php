<?php

declare(strict_types=1);

namespace App\Services;

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class IdentityAuthentication
{
    public function login(Request $request, string $guard, array $input): Staff|User
    {
        abort_unless(in_array($guard, ['staff', 'merchant'], true), 500);
        $phone = PhoneNumber::normalize($input['phone']);
        $key = hash('sha256', 'login|'.$guard.'|'.$phone.'|'.$request->ip());
        $accountKey = hash('sha256', 'account-login|'.$guard.'|'.$phone);

        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($accountKey, 20)) {
            abort(429, 'محاولات كثيرة. انتظر قليلًا ثم حاول مجددًا.');
        }

        $account = DB::transaction(function () use ($request, $guard, $input, $phone): Staff|User|null {
            $auth = Auth::guard($guard);
            $allowed = $guard === 'staff' ? ['active'] : ['active', 'pending'];
            $credentials = [
                'phone' => $phone,
                'password' => $input['password'],
                fn (Builder $query) => $query->whereIn('status', $allowed)->lockForUpdate(),
            ];

            if (! $auth->attempt($credentials, false)) {
                return null;
            }

            $account = $auth->user();
            // One identity per browser session: never leave a staff login active behind a merchant login.
            $this->logout($request);
            $auth->login($account, false);
            $account->forceFill(['last_login_at' => now()])->save();
            $request->session()->put('delivery_auth.'.$guard.'.version', $account->session_version);
            Auth::shouldUse($guard);

            return $account;
        });

        if (! $account) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($accountKey, 900);
            throw ValidationException::withMessages(['phone' => 'تعذّر تسجيل الدخول. تحقق من البيانات أو حالة الحساب.']);
        }

        RateLimiter::clear($key);
        RateLimiter::clear($accountKey);

        return $account;
    }

    public function establishMerchantSession(Request $request, User $user): void
    {
        $user = $user->fresh();
        $this->logout($request);
        Auth::guard('merchant')->login($user, false);
        $request->session()->put('delivery_auth.merchant.version', $user->session_version);
    }

    public function logout(Request $request): void
    {
        Auth::guard('staff')->logout();
        Auth::guard('merchant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function staffHome(Staff $staff): string
    {
        if ($staff->must_change_password) {
            return route('staff.password.edit');
        }
        if ($staff->hasAnyRole(['super_admin', 'operations', 'finance'])) {
            return route('admin.dashboard');
        }

        return route('captain.dashboard');
    }
}
