@extends('layouts.base')
@section('title', 'تعذّر إكمال العملية')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>500</strong><h1>تعذّر إكمال العملية</h1><p>حاول مجددًا بعد قليل. إذا استمرت المشكلة، راجع فريق الإدارة.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
