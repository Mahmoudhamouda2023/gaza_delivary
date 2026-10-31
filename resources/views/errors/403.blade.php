@extends('layouts.base')
@section('title', 'هذه الصفحة خارج صلاحيات حسابك')
@section('body')
<main id="main" class="error-page"><x-brand/><strong>403</strong><h1>هذه الصفحة خارج صلاحيات حسابك</h1><p>ارجع لمساحتك، أو راجع الإدارة إذا كنت تحتاج هذه الصلاحية.</p><a class="button button-primary" href="{{ route('home') }}">الرئيسية <x-icon name="arrow"/></a></main>
@endsection
