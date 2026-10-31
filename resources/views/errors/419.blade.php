@extends('layouts.base')
@section('title', 'انتهت صلاحية الصفحة')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>419</strong><h1>انتهت صلاحية الصفحة</h1><p>أعد فتح الصفحة ثم أرسل البيانات مجددًا.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
