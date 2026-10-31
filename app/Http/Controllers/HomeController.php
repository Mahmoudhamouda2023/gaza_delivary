<?php

namespace App\Http\Controllers;

use App\Services\IdentityAuthentication;
use Illuminate\Support\Facades\Auth;

final class HomeController extends Controller
{
    public function __invoke(IdentityAuthentication $authentication)
    {
        if (Auth::guard('staff')->check()) {
            return redirect($authentication->staffHome(Auth::guard('staff')->user()));
        }
        if (Auth::guard('merchant')->check()) {
            return redirect()->route('merchant.dashboard');
        }

        return view('home');
    }
}
