@extends('layouts.auth')
@section('title', $guard === 'staff' ? 'دخول فريق العمل' : 'دخول المتجر')
@section('content')
<span class="square-icon"><x-icon :name="$guard === 'staff' ? 'users' : 'store'"/></span><div class="form-heading"><span class="eyebrow">{{ $guard === 'staff' ? 'الإدارة والكباتن' : 'مساحة التجار' }}</span><h2>{{ $guard === 'staff' ? 'أهلًا بفريقنا' : 'أهلًا برجعتك' }}</h2><p>{{ $guard === 'staff' ? 'ادخل بحساب العمل المخصص إلك.' : 'ادخل لحسابك وتابع حالة متجرك.' }}</p></div>
<form method="post" action="{{ route($guard.'.login.store') }}" class="form-stack">@csrf
    <x-field name="phone" label="رقم الجوال" type="tel" dir="ltr" autocomplete="username" required placeholder="0591234567" maxlength="10" minlength="10" inputmode="numeric" pattern="[0٠۰][5٥۵][0-9٠-٩۰-۹]{8}"/>
    <x-field name="password" label="كلمة المرور" type="password" autocomplete="current-password" required maxlength="256"/>
    <button class="button button-primary button-block" type="submit">تسجيل الدخول <x-icon name="arrow"/></button>
</form>
@if($guard === 'merchant')<p class="form-switch">أول مرة معنا؟ <a href="{{ route('register') }}">سجّل متجرك</a></p>@else<p class="form-switch">حسابات فريق العمل تنشئها الإدارة.</p>@endif
<div class="subtle-note"><x-icon name="shield"/><span>استخدم حسابك الشخصي، وسجّل الخروج بعد انتهاء العمل على جهاز مشترك.</span></div>
@endsection
