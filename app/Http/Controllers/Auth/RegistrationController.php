<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMerchantRequest;
use App\Services\IdentityAuthentication;
use GazaDelivery\Foundation\Services\MerchantRegistration;

final class RegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterMerchantRequest $request, MerchantRegistration $registration, IdentityAuthentication $authentication)
    {
        $user = $registration->register($request->validated());
        $authentication->establishMerchantSession($request, $user);

        return redirect()->route('merchant.status')->with('status', 'وصلنا طلب تسجيل متجرك. سنراجع البيانات ونتواصل معك.');
    }
}
