@extends('layouts.base')
@section('title', 'الصفحة غير موجودة')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>404</strong><h1>الصفحة غير موجودة</h1><p>قد يكون الرابط غير صحيح، أو الصفحة غير متاحة لحسابك.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
