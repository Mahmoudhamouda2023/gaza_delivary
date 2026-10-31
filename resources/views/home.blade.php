@extends('layouts.base')
@section('title', 'أهلًا بمتجرك')
@push('head')<link rel="stylesheet" href="{{ asset('assets/public-pages.css') }}?v={{ config('delivery.release') }}">@endpush
@section('body')
<div class="landing"><header class="landing-header"><x-brand/><nav class="landing-nav" aria-label="الصفحات العامة"><a href="{{ route('public.about') }}">من نحن</a><a href="{{ route('public.captains') }}">للكباتن</a><a href="{{ route('public.terms') }}">الشروط والأحكام</a><a class="button button-secondary button-small" href="{{ route('staff.login') }}">دخول فريق العمل <x-icon name="arrow"/></a></nav></header>
    <main id="main" class="landing-main"><section class="landing-copy"><span class="eyebrow"><span class="status-dot"></span> من غزة، لمتاجر غزة</span><h1>من متجرك<br><span>لزبونك.</span></h1><p>بوابتك للتواصل مع فريق التوصيل في غزة والشمال. سجّل متجرك، وخلّينا نرتّب بداية شغلك معنا.</p><div class="button-row"><a class="button button-primary" href="{{ route('register') }}">سجّل متجرك <x-icon name="arrow"/></a><a class="button button-secondary" href="{{ route('login') }}">عندي حساب</a></div><div class="landing-notes"><span><x-icon name="phone"/> مناسب للجوال</span><span><x-icon name="shield"/> حساب مستقل لمتجرك</span></div></section>
        <figure class="brand-hero"><img src="{{ asset('assets/hero-metjari.png') }}" width="1254" height="1254" fetchpriority="high" alt="متجري، نوصل طلبات متجرك بكل احترافية، من متجرك لزبونك"></figure>
    </main><footer class="landing-footer">متجري <span>·</span> من متجرك لزبونك</footer>
</div>
@endsection
