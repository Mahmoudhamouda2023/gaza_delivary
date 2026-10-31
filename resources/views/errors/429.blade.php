@extends('layouts.base')
@section('title', 'خلّينا نحاول بعد قليل')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>429</strong><h1>خلّينا نحاول بعد قليل</h1><p>وصلنا عدد كبير من المحاولات. انتظر قليلًا ثم حاول مجددًا.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
