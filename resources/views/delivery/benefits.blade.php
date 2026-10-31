@extends('layouts.shell')
@section('title','هدايا التوصيل')
@section('content')
@php $money=\App\Services\Delivery\Money::class; @endphp
<div class="page-heading"><div><span class="eyebrow">مزايا متجرك</span><h1>هدايا التوصيل</h1><p>خصومات واضحة، ورصيد توصيل تعرفه قبل كل طلب.</p></div></div>
<div class="notice">خصم اتفاق متجرك الساري: {{ number_format(($agreement?->discount_bps??0)/100,2) }}%. لا نجمع الخصومات؛ تُطبّق الميزة الأفضل على أجرة التوصيل. التوصيلات المجانية لا تشمل رسوم إعادة الطرد.</div>
<section class="panel section-space"><h2>هدايا التوصيل المتاحة</h2>@forelse($credits as $c)<div class="review-note"><strong>{{ $c->remaining }} توصيلات مجانية</strong><p>{{ $c->reason }}</p><small>حتى {{ $c->expires_on->format('Y/m/d') }}</small></div>@empty<p>ستظهر هدايا متجرك هنا عند منحها.</p>@endforelse</section>
@endsection
