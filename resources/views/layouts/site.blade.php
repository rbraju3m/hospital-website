<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-28">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', setting('site_name', 'RBR Hospital')) — {{ setting('site_name', 'RBR Hospital') }}</title>
    <meta name="description" content="@yield('meta_description', setting('site_tagline'))">

    <meta property="og:site_name" content="{{ setting('site_name') }}">
    <meta property="og:title" content="@yield('title', setting('site_name'))">
    <meta property="og:description" content="@yield('meta_description', setting('site_tagline'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#0b2c4d">
    <link rel="canonical" href="{{ url()->current() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-white pb-16 lg:pb-0">

<a href="#main"
   class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:rounded-full
          focus:bg-navy-900 focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-white">
    Skip to main content
</a>

@include('partials.header')

<main id="main">
    @yield('content')
</main>

@include('partials.footer')
@include('partials.mobile-action-bar')

@stack('scripts')
</body>
</html>
