@extends('layouts.base')
@php
    $guard = request()->is('admin', 'admin/*', 'staff/*', 'captain', 'captain/*') ? 'staff' : 'merchant';
    $identity = auth($guard)->user();
    $isAdmin = $guard === 'staff' && $identity->hasAnyRole(['super_admin', 'operations', 'finance']);
    $dashboard = $guard === 'merchant' ? 'merchant.dashboard' : ($isAdmin ? 'admin.dashboard' : 'captain.dashboard');
@endphp
@section('body')
@if($guard === 'merchant')
<div class="merchant-app">
<header class="merchant-top"><x-brand/><div class="merchant-account"><span class="avatar">{{ mb_substr($identity->store?->name ?? $identity->name,0,1) }}</span><span>{{ $identity->store?->name ?? $identity->name }}<small>مساحة المتجر</small></span></div></header>
<main id="main" class="merchant-main"><x-feedback/>@yield('content')</main>
<nav class="merchant-nav" aria-label="قائمة المتجر">
<a href="{{ route('merchant.dashboard') }}" @class(['active'=>request()->routeIs('merchant.dashboard')])><x-icon name="grid"/><span>الرئيسية</span></a>
<a href="{{ route('merchant.orders.index') }}" @class(['active'=>request()->routeIs('merchant.orders.index','merchant.orders.show')])><x-icon name="truck"/><span>طلباتي</span></a>
<a href="{{ route('merchant.orders.create') }}" @class(['new-order-nav','active'=>request()->routeIs('merchant.orders.create','merchant.orders.preview')])><x-icon name="plus"/><span>طلب جديد</span></a>
<a href="{{ route('merchant.finance') }}" @class(['active'=>request()->routeIs('merchant.finance*')])><x-icon name="wallet"/><span>حسابي</span></a>
<details class="merchant-more"><summary><x-icon name="more"/><span>المزيد</span></summary><div class="more-menu"><a href="{{ route('merchant.addresses.index') }}">عناوين الاستلام</a><a href="{{ route('merchant.benefits.index') }}">هداياي</a><a href="{{ route('merchant.password.edit') }}">كلمة المرور</a><form action="{{ route('merchant.logout') }}" method="post">@csrf<button type="submit">تسجيل الخروج</button></form></div></details>
</nav></div>
@else
<div class="app-layout"><aside class="sidebar"><x-brand/><div class="workspace-label">{{ $guard === 'merchant' ? 'مساحة متجرك' : ($isAdmin ? 'مساحة الإدارة' : 'مساحة الكابتن') }}</div>
    <nav class="side-nav" aria-label="القائمة الرئيسية">
        <a href="{{ route($dashboard) }}" @class(['active' => request()->routeIs($dashboard)])><x-icon name="grid"/>الرئيسية</a>
        @if($isAdmin)
        <details class="nav-group" @if(request()->routeIs('admin.orders.*','admin.issues','captain.*')) open @endif>
            <summary><x-icon name="truck"/><span>الطلبات والتشغيل</span></summary><div class="nav-group-links">
                <a href="{{ route('admin.orders.index') }}" @class(['active'=>request()->routeIs('admin.orders.*')])>الطلبات</a>
                @can('operate-delivery')<a href="{{ route('admin.issues') }}" @class(['active'=>request()->routeIs('admin.issues')])>متابعة الملاحظات</a>@endcan
                @if($identity->hasRole('captain'))<a href="{{ route('captain.dashboard') }}">مساحة الكابتن</a>@endif
            </div>
        </details>
        @if($identity->can('stores.view') || $identity->can('configure-delivery'))
        <details class="nav-group" @if(request()->routeIs('admin.stores.*','admin.subscriptions') || request()->is('admin/catalog/agreements*','admin/catalog/coupons*')) open @endif>
            <summary><x-icon name="store"/><span>المتاجر والمزايا</span></summary><div class="nav-group-links">
                @can('stores.view')<a href="{{ route('admin.stores.index') }}" @class(['active'=>request()->routeIs('admin.stores.*')])>المتاجر</a>@endcan
                @can('configure-delivery')
                @foreach(['agreements'=>'اتفاقيات المتاجر','coupons'=>'الكوبونات'] as $key=>$label)
                <a href="{{ route('admin.catalog.index',$key) }}" @class(['active'=>request()->routeIs('admin.catalog.*') && request()->route('kind')===$key])>{{ $label }}</a>
                @endforeach
                <a href="{{ route('admin.subscriptions') }}" @class(['active'=>request()->routeIs('admin.subscriptions')])>هدايا التوصيل</a>
                @endcan
            </div>
        </details>
        @endif
        @can('finance-delivery')
        <details class="nav-group" @if(request()->routeIs('admin.finance*','admin.reports','admin.payments.*','admin.pricing-study')) open @endif>
            <summary><x-icon name="wallet"/><span>المالية والتقارير</span></summary><div class="nav-group-links">
                <a href="{{ route('admin.finance') }}" @class(['active'=>request()->routeIs('admin.finance*')])>المالية والتسويات</a>
                <a href="{{ route('admin.payments.index') }}" @class(['active'=>request()->routeIs('admin.payments.*')])>دفعات المحافظ والبنوك</a>
                <a href="{{ route('admin.reports') }}" @class(['active'=>request()->routeIs('admin.reports')])>تقارير التشغيل</a>
                <a href="{{ route('admin.pricing-study') }}" @class(['active'=>request()->routeIs('admin.pricing-study')])>حاسبة أجرة المكتب</a>
            </div>
        </details>
        @endcan
        @can('configure-delivery')
        <details class="nav-group" @if(request()->is('admin/catalog/zones*','admin/catalog/neighborhoods*','admin/catalog/tariffs*')) open @endif>
            <summary><x-icon name="pin"/><span>إعدادات التوصيل</span></summary><div class="nav-group-links">
                @foreach(['zones'=>'المناطق','neighborhoods'=>'الأحياء','tariffs'=>'أسعار المسارات'] as $key=>$label)
                <a href="{{ route('admin.catalog.index',$key) }}" @class(['active'=>request()->routeIs('admin.catalog.*') && request()->route('kind')===$key])>{{ $label }}</a>
                @endforeach
            </div>
        </details>
        @endcan
        @if($identity->can('staff.view') || $identity->can('manage-staff'))
        <details class="nav-group" @if(request()->routeIs('admin.staff.*','admin.team.*','admin.audit')) open @endif>
            <summary><x-icon name="users"/><span>الفريق والصلاحيات</span></summary><div class="nav-group-links">
                @can('staff.view')<a href="{{ route('admin.staff.index') }}" @class(['active'=>request()->routeIs('admin.staff.*','admin.team.*')])>فريق العمل</a>@endcan
                @can('manage-staff')<a href="{{ route('admin.audit') }}" @class(['active'=>request()->routeIs('admin.audit')])>سجل العمليات</a>@endcan
            </div>
        </details>
        @endif
        @else
        <details class="nav-group" open><summary><x-icon name="truck"/><span>عمل الكابتن</span></summary><div class="nav-group-links">
            <a href="{{ route('captain.orders.index') }}" @class(['active'=>request()->routeIs('captain.orders.*')])>الطلبات</a>
            <a href="{{ route('captain.finance') }}" @class(['active'=>request()->routeIs('captain.finance')])>التحصيل والمستحقات</a>
        </div></details>
        @endif
        <details class="nav-group" @if(request()->routeIs($guard.'.password.*')) open @endif><summary><x-icon name="lock"/><span>الحساب</span></summary><div class="nav-group-links">
            <a href="{{ route($guard.'.password.edit') }}" @class(['active'=>request()->routeIs($guard.'.password.*')])>أمان الحساب</a>
        </div></details>
    </nav><div class="sidebar-bottom"><span class="avatar">{{ mb_substr($identity->name, 0, 1) }}</span><div><strong>{{ $identity->name }}</strong><small>{{ $guard === 'merchant' ? 'صاحب متجر' : 'فريق متجري' }}</small></div></div></aside>
    <div class="workspace"><header class="topbar"><div><span class="breadcrumb">متجري <span>/</span> @yield('breadcrumb', 'الرئيسية')</span><strong class="mobile-brand">متجري</strong></div><div class="topbar-actions"><span class="date-label">{{ now()->timezone('Asia/Hebron')->format('Y/m/d') }}</span><form method="post" action="{{ route($guard.'.logout') }}">@csrf<button class="button button-quiet button-small" type="submit"><x-icon name="logout"/><span>خروج</span></button></form></div></header>
        <main id="main" class="workspace-main"><x-feedback/>@yield('content')</main><footer class="workspace-footer"><span>متجري</span><span>غزة والشمال</span></footer>
    </div>
</div>
@endif
@endsection
