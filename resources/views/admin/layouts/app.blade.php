<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-shell">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('admin.panel')) — {{ __('admin.panel') }}</title>

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

                    // A collapsed menu names its items in a tooltip the bundle
                    // draws. Without the bundle that is fifteen unlabelled
                    // icons, so put the labels back rather than the animation.
                    document.documentElement.classList.remove('panel-rail');
                }
            }, 1500);

            try {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);

                // The sidebar's collapsed state, for the same reason: settled
                // here or the menu is 18rem wide for one frame and snaps.
                if (localStorage.getItem('panel-rail') === '1') {
                    document.documentElement.classList.add('panel-rail');
                }
            } catch (error) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist-50 text-navy-900/85">

<div x-data="{ menu: false }" class="lg:flex">

    {{-- Off-canvas below lg, permanent from lg up. --}}
    <div x-show="menu" x-cloak @click="menu = false" x-transition.opacity.duration.200ms
         class="fixed inset-0 z-30 bg-navy-950/50 dark:bg-navy-50/50 backdrop-blur-sm lg:hidden"></div>

    <aside :class="menu && 'is-open'"
           class="admin-drawer admin-sidebar fixed inset-y-0 left-0 z-40 flex flex-col bg-navy-950 dark:bg-navy-50 shadow-lift
                  lg:sticky lg:top-0 lg:h-screen lg:shadow-none">
        @include('admin.partials.sidebar')
    </aside>

    <div class="flex min-h-screen w-full flex-1 flex-col">
        @include('admin.partials.topbar')

        <main class="anim-fade-in flex-1 px-5 py-7 sm:px-8 lg:px-10">
            @include('admin.partials.flash')

            @yield('content')
        </main>

        <footer class="px-5 pb-8 text-xs text-navy-900/40 sm:px-8 lg:px-10">
            {{ __('admin.footer', ['name' => setting('site_name', 'RBR Hospital'), 'year' => now()->year]) }}
        </footer>
    </div>
</div>

@include('admin.partials.palette')

{{-- The collapsed rail's tooltip. One node, moved and filled by app.js: the
     sidebar's nav scrolls, and anything drawn inside it would be clipped by
     that overflow the moment it reached past 4.5rem. --}}
<div class="panel-tip" data-panel-tip-box hidden></div>

@stack('scripts')
</body>
</html>
