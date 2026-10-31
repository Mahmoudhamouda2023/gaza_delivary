@extends('layouts.base')
@section('body')
<div class="auth-layout">
    <aside class="auth-story"><x-brand/>
        <div class="story-content"><span class="eyebrow eyebrow-light"><span class="status-dot"></span> خدمة المتاجر في غزة والشمال</span><h1>من متجرك،<br>لحد باب زبونك.</h1><p>مكان واحد يجمع متجرك وفريق التوصيل، ويخلّي المتابعة أوضح من أول خطوة.</p>
            <div class="route-illustration" aria-hidden="true"><span><x-icon name="store"/></span><i></i><span class="route-center"><x-icon name="truck"/></span><i></i><span><x-icon name="pin"/></span></div>
            <div class="story-points"><span><x-icon name="shield"/> وصول حسب صلاحية الحساب</span><span><x-icon name="phone"/> واجهة تناسب جوالك</span></div>
        </div><div class="story-footer">قريبين من متجرك، خطوة بخطوة.</div>
    </aside>
    <div class="auth-main"><header class="auth-top"><a href="{{ route('home') }}"><x-icon name="arrow"/> الرئيسية</a><span>غزة · الشمال</span></header><main id="main" class="auth-form-wrap"><x-feedback/>@yield('content')</main><footer class="auth-footer">متجري <span>·</span> {{ now()->year }}</footer></div>
</div>
@endsection
