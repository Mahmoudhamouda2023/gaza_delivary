@extends('layouts.base')
@section('title', 'نرتّب بعض الأمور')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>503</strong><h1>نرتّب بعض الأمور</h1><p>الخدمة غير متاحة مؤقتًا. جرّب مجددًا بعد قليل.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
