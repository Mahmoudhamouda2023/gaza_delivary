<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffcf24">
    <title>@yield('title', 'متجري') · متجري</title>
    <link rel="icon" href="{{ asset('assets/mark-metjari.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ config('delivery.release') }}">
    @if(request()->is('merchant*','captain*','admin*','staff/password'))<script src="{{ asset('assets/store.js') }}?v={{ config('delivery.release') }}" defer></script>@endif
    <script src="{{ asset('assets/theme.js') }}?v={{ config('delivery.release') }}"></script>
    @stack('head')
    <link rel="stylesheet" href="{{ asset('assets/theme.css') }}?v={{ config('delivery.release') }}">
</head>
<body>
    <a class="skip-link" href="#main">انتقل إلى المحتوى</a>
    <button type="button" id="theme-toggle" class="theme-toggle" aria-label="تبديل المظهر" title="تبديل المظهر"><span aria-hidden="true">☾</span></button>
    @yield('body')
    @stack('scripts')
</body>
</html>
