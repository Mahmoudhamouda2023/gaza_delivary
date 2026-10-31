@extends('layouts.base')
@section('title', 'الجلسة تحتاج تسجيل دخول')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>401</strong><h1>الجلسة تحتاج تسجيل دخول</h1><p>انتهت الجلسة أو لم تبدأ بعد. سجّل الدخول مجددًا.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
