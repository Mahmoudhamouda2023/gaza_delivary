<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\IdentityAuthentication;
use App\Services\PasswordChange;
use Illuminate\Http\Request;

final class PasswordController extends Controller
{
    public function staffEdit(Request $request)
    {
        return view('auth.password', ['guard' => 'staff', 'account' => $request->user('staff')]);
    }

    public function merchantEdit(Request $request)
    {
        return view('auth.password', ['guard' => 'merchant', 'account' => $request->user('merchant')]);
    }

    public function staffUpdate(ChangePasswordRequest $request, PasswordChange $passwords, IdentityAuthentication $authentication)
    {
        $passwords->change($request, 'staff', $request->validated());

        return redirect($authentication->staffHome($request->user('staff')))->with('status', 'تم تغيير كلمة المرور وإنهاء صلاحية الجلسات الأخرى.');
    }

    public function merchantUpdate(ChangePasswordRequest $request, PasswordChange $passwords)
    {
        $passwords->change($request, 'merchant', $request->validated());

        return redirect()->route('merchant.dashboard')->with('status', 'تم تغيير كلمة المرور وإنهاء صلاحية الجلسات الأخرى.');
    }
}
