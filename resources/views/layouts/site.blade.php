<!DOCTYPE html>
{{-- `no-motion` mirrors prefers-reduced-motion, for the Site controls switch
     that turns the site's movement off for everybody. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="scroll-pt-28 @unless (feature('behaviour_animations')) no-motion @endunless">
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
    @if (feature('chrome_locale_switcher'))
        @foreach (array_keys(config('app.available_locales', [])) as $altLocale)
            <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ route('locale.switch', $altLocale) }}">
        @endforeach
    @endif

    {{-- Settle the theme before the first paint. Anything later and the page
         flashes the other one on every navigation. --}}
    <script>
        (function () {
            // Reveals are hidden until this class says the animation can run.
            // It is set here so nothing flashes visible before being hidden —
            // but this script running only proves *inline* script runs, not
            // that the bundle did. If app.js has not reported in shortly, drop
            // the class again and let the page show itself.
            document.documentElement.classList.add('has-js');

            setTimeout(function () {
                if (! document.documentElement.classList.contains('js-ready')) {
                    document.documentElement.classList.remove('has-js');
                }
            }, 1500);

            try {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (error) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-white dark:bg-navy-50 {{ feature('chrome_mobile_bar') ? 'pb-16 lg:pb-0' : '' }}">

<a href="#main"
   class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:rounded-full
          focus:bg-navy-900 focus:dark:bg-navy-100 focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-white">
    {{ __('common.skip_to_content') }}
</a>

{{-- Reading progress. Decorative and driven entirely by app.js, so it is
     hidden from assistive tech rather than announced as a live value. --}}
@if (feature('chrome_scroll_progress'))
    <div class="scroll-progress" data-scroll-progress aria-hidden="true"><span></span></div>
@endif

@include('partials.header')

<main id="main">
    @yield('content')
</main>

@include('partials.footer')

@if (feature('chrome_mobile_bar'))
    @include('partials.mobile-action-bar')
@endif

@if (feature('chrome_back_to_top'))
    <button type="button" class="to-top" data-to-top aria-label="{{ __('common.back_to_top') }}">
        <x-icon name="arrow-up" size="18" stroke="2.2" />
    </button>
@endif

@stack('scripts')
</body>
</html>
