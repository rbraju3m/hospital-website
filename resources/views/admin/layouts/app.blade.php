<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('admin.panel')) — {{ __('admin.panel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist-50 text-navy-900/85">

<div x-data="{ menu: false }" class="lg:flex">

    {{-- Off-canvas below lg, permanent from lg up. --}}
    <div x-show="menu" x-cloak @click="menu = false"
         class="fixed inset-0 z-30 bg-navy-950/50 lg:hidden"></div>

    <aside x-cloak
           :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-navy-950 transition-transform
                  duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        @include('admin.partials.sidebar')
    </aside>

    <div class="flex min-h-screen w-full flex-1 flex-col">
        @include('admin.partials.topbar')

        <main class="flex-1 px-5 py-7 sm:px-8 lg:px-10">
            @include('admin.partials.flash')

            @yield('content')
        </main>

        <footer class="px-5 pb-8 text-xs text-navy-900/40 sm:px-8 lg:px-10">
            {{ __('admin.footer', ['name' => setting('site_name', 'RBR Hospital'), 'year' => now()->year]) }}
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
