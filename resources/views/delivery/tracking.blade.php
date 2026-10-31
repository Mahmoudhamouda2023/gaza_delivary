@extends('layouts.base')
@section('title','تتبع التوصيل')
@section('body')
<main id="main" class="tracking-page"><x-brand/><section class="panel section-space"><span class="eyebrow">{{ $order->store->name }}</span><h1>{{ $order->label }}</h1><p>طلب {{ substr($order->public_id,-10) }}</p><p>موعد التوصيل المطلوب: {{ $order->delivery_time_label }}</p><ol class="timeline">@foreach($order->events as $e)<li><strong>{{ config('delivery.order_statuses.'.$e->status) }}</strong><small class="cell-note">{{ $e->created_at->timezone('Asia/Hebron')->format('Y/m/d H:i') }}</small></li>@endforeach</ol><p class="muted">لأي استفسار تواصل مع المتجر. آخر تحديث {{ $order->updated_at->timezone('Asia/Hebron')->format('m/d H:i') }}.</p></section></main>
@endsection
