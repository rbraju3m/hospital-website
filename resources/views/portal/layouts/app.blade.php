<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- A patient's own records: never indexable. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('portal.name')) — {{ setting('site_name', 'RBR Hospital') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist-50">

@include('portal.partials.header')

<main class="shell py-10">
    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">
            <x-icon name="check-circle" size="18" class="mt-0.5 shrink-0 text-teal-600" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    @yield('content')
</main>

<footer class="shell pb-10 text-xs text-navy-900/40">
    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-mist-200 pt-6">
        <p>{{ setting('site_name', 'RBR Hospital') }} · {{ __('portal.name') }}</p>
        <a href="{{ route('home') }}" class="hover:text-navy-900">{{ __('portal.back_to_site') }}</a>
    </div>
</footer>

</body>
</html>
