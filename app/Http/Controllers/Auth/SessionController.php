<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\IdentityAuthentication;
use Illuminate\Http\Request;

final class SessionController extends Controller
{
    public function merchantCreate()
    {
        return view('auth.login', ['guard' => 'merchant']);
    }

    public function staffCreate()
    {
        return view('auth.login', ['guard' => 'staff']);
    }

    public function merchantStore(LoginRequest $request, IdentityAuthentication $authentication)
    {
        $authentication->login($request, 'merchant', $request->validated());

        return redirect()->route('merchant.dashboard');
    }

    public function staffStore(LoginRequest $request, IdentityAuthentication $authentication)
    {
        $account = $authentication->login($request, 'staff', $request->validated());

        return redirect($authentication->staffHome($account));
    }

    public function merchantDestroy(Request $request, IdentityAuthentication $authentication)
    {
        $authentication->logout($request);

        return redirect()->route('login');
    }

    public function staffDestroy(Request $request, IdentityAuthentication $authentication)
    {
        $authentication->logout($request);

        return redirect()->route('staff.login');
    }
}
