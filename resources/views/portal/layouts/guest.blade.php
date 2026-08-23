<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('portal.name')) — {{ setting('site_name', 'RBR Hospital') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-mist-100 px-5 py-12">

<div class="w-full max-w-md">
    <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-navy-900 font-display text-sm font-extrabold text-white">
            RBR
        </span>
        <span>
            <span class="block font-display text-base font-bold text-navy-900">{{ setting('site_name', 'RBR Hospital') }}</span>
            <span class="block text-xs text-navy-900/50">{{ __('portal.name') }}</span>
        </span>
    </a>

    <div class="card p-8">
        <h1 class="font-display text-xl font-bold text-navy-900">@yield('heading')</h1>
        <p class="mt-1.5 text-sm text-navy-900/55">@yield('lede')</p>

        @if (session('status'))
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                <x-icon name="check-circle" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @yield('form')
    </div>

    <div class="mt-6 flex items-center justify-between text-xs">
        <a href="{{ route('home') }}" class="text-navy-900/45 hover:text-navy-900">← {{ __('portal.back_to_site') }}</a>
        <x-locale-switcher variant="drawer" class="!border-0 !p-0" />
    </div>
</div>

</body>
</html>
