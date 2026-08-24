<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('pages.maintenance.title') }} — {{ setting('site_name') }}</title>
    @vite(['resources/css/app.css'])
</head>
{{-- Standalone rather than extending the site layout: the header links to areas
     that may themselves be switched off, and this page has exactly one job. --}}
<body class="grid min-h-screen place-items-center bg-navy-950 dark:bg-navy-50 px-5 text-white">

<div aria-hidden="true" class="hero-grid opacity-[0.10]"></div>
<div aria-hidden="true" class="orb -right-40 -top-40 h-[30rem] w-[30rem] bg-teal-500/20"></div>

<main class="relative w-full max-w-lg text-center">
    <span class="anim-scale-in mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-teal-600 text-white shadow-lift">
        <x-icon name="heart-pulse" size="32" stroke="2" />
    </span>

    <h1 class="anim-fade-up mt-7 font-display text-3xl font-bold text-white sm:text-4xl" style="--anim-delay:80ms">
        {{ __('pages.maintenance.heading') }}
    </h1>

    <p class="anim-fade-up mt-4 text-white/65" style="--anim-delay:160ms">
        {{ __('pages.maintenance.body', ['name' => setting('site_name')]) }}
    </p>

    {{-- The numbers are the point of the page. Nobody should have to wait for
         the website to come back to reach an emergency line. --}}
    <div class="anim-fade-up mt-9 grid gap-3 sm:grid-cols-2" style="--anim-delay:240ms">
        <a href="tel:{{ setting('hotline') }}"
           class="group rounded-2xl border border-white/10 bg-white/5 p-5 transition duration-300 ease-out
                  hover:-translate-y-1 hover:border-teal-400/40 hover:bg-white/10">
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300
                         transition duration-300 group-hover:scale-110">
                <x-icon name="phone-call" size="19" />
            </span>
            <span class="mt-3 block text-xs text-white/50">{{ __('pages.maintenance.hotline_label') }}</span>
            <span class="block font-display text-xl font-bold text-white">{{ setting('hotline') }}</span>
        </a>

        <a href="tel:{{ setting('ambulance_number') }}"
           class="group rounded-2xl border border-urgent-500/30 bg-urgent-500/10 p-5 transition duration-300 ease-out
                  hover:-translate-y-1 hover:border-urgent-500/60 hover:bg-urgent-500/20">
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-urgent-500/20 text-urgent-100
                         transition duration-300 group-hover:scale-110">
                <x-icon name="ambulance" size="19" />
            </span>
            <span class="mt-3 block text-xs text-white/50">{{ __('pages.maintenance.ambulance_label') }}</span>
            <span class="block font-display text-xl font-bold text-white">{{ setting('ambulance_number') }}</span>
        </a>
    </div>

    <p class="anim-fade-in mt-8 text-xs text-white/40" style="--anim-delay:320ms">
        {{ setting('address_line') }}, {{ setting('address_city') }}
    </p>
</main>

</body>
</html>
