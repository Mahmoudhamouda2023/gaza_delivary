<?php

declare(strict_types=1);

namespace App\Services;

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Services\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PasswordChange
{
    public function change(Request $request, string $guard, array $data): void
    {
        abort_unless(in_array($guard, ['staff', 'merchant'], true), 500);
        $account = DB::transaction(function () use ($request, $guard, $data): Staff|User {
            $model = $guard === 'staff' ? Staff::class : User::class;
            $account = $model::query()->lockForUpdate()->findOrFail($request->user($guard)->getKey());
            if (! Hash::check($data['current_password'], $account->password)) {
                throw ValidationException::withMessages(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
            }
            if (Hash::check($data['password'], $account->password)) {
                throw ValidationException::withMessages(['password' => 'اختر كلمة مرور مختلفة عن الحالية.']);
            }

            $account->forceFill([
                'password' => $data['password'], 'must_change_password' => false,
                'session_version' => $account->session_version + 1, 'remember_token' => null,
            ])->save();
            app(AuditRecorder::class)->record('account.password_changed', $account, $account instanceof Staff ? $account : null);

            return $account;
        });

        Auth::guard($guard)->setUser($account);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('delivery_auth.'.$guard.'.version', $account->session_version);
    }
}
