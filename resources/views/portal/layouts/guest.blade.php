<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('portal.name')) — {{ setting('site_name', 'RBR Hospital') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative grid min-h-screen place-items-center overflow-hidden bg-mist-100 px-5 py-12">

{{-- Two very soft washes of brand colour so the sign-in is not a white box on
     grey. Decorative only — nothing here carries meaning. --}}
<div aria-hidden="true" class="orb -top-32 -left-24 h-96 w-96 bg-teal-300/25"></div>
<div aria-hidden="true" class="orb -bottom-40 -right-24 h-96 w-96 bg-navy-200/40" style="--anim-delay:-6s"></div>

<div class="relative w-full max-w-md">
    <a href="{{ route('home') }}" class="anim-fade-up group mb-8 flex items-center justify-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-navy-900 dark:bg-navy-100 font-display text-sm font-extrabold text-white
                     transition duration-300 ease-out group-hover:scale-105 group-hover:bg-teal-600">
            RBR
        </span>
        <span>
            <span class="block font-display text-base font-bold text-navy-900">{{ setting('site_name', 'RBR Hospital') }}</span>
            <span class="block text-xs text-navy-900/50">{{ __('portal.name') }}</span>
        </span>
    </a>

    <div class="card anim-scale-in p-8" style="--anim-delay:100ms">
        <h1 class="font-display text-xl font-bold text-navy-900">@yield('heading')</h1>
        <p class="mt-1.5 text-sm text-navy-900/55">@yield('lede')</p>

        @if (session('status'))
            <div class="alert-success mt-5 rounded-xl px-4 py-3">
                <x-icon name="check-circle" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @yield('form')
    </div>

    <div class="anim-fade-in mt-6 flex items-center justify-between text-xs" style="--anim-delay:260ms">
        <a href="{{ route('home') }}" class="text-navy-900/45 transition duration-200 hover:text-navy-900">← {{ __('portal.back_to_site') }}</a>
        <x-locale-switcher variant="drawer" class="!border-0 !p-0" />
    </div>
</div>

</body>
</html>
