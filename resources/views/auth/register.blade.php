@extends('layouts.auth')
@section('title', 'تسجيل متجر')
@section('content')
<div class="form-heading"><span class="eyebrow">انضم للتجار</span><h2>نبدأ بمتجرك</h2><p>بيانات بسيطة، وبعدها فريقنا يراجع طلبك ويتواصل معك.</p></div>
<form method="post" action="{{ route('register.store') }}" class="form-stack">@csrf
    <div class="form-grid"><x-field name="name" label="اسم صاحب المتجر" autocomplete="name" required maxlength="120"/><x-field name="store_name" label="اسم المتجر" autocomplete="organization" required maxlength="160"/></div>
    <x-field name="phone" label="رقم الجوال" type="tel" dir="ltr" autocomplete="tel" required placeholder="0591234567" hint="رقم نقدر نتواصل معك من خلاله." maxlength="10" minlength="10" inputmode="numeric" pattern="[0٠۰][5٥۵][0-9٠-٩۰-۹]{8}"/>
    <x-field name="email" label="البريد الإلكتروني (اختياري)" type="email" dir="ltr" autocomplete="email" maxlength="254"/>
    <x-field name="password" label="كلمة المرور" type="password" autocomplete="new-password" required minlength="8" maxlength="256" hint="8 خانات على الأقل، تتضمن أحرفًا وأرقامًا."/>
    <x-field name="password_confirmation" label="تأكيد كلمة المرور" type="password" autocomplete="new-password" required minlength="8" maxlength="256"/>
    <button class="button button-primary button-block" type="submit">إرسال طلب التسجيل <x-icon name="arrow"/></button>
</form><p class="form-switch">عندك حساب؟ <a href="{{ route('login') }}">سجّل الدخول</a></p>
@endsection
