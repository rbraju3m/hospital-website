<header class="sticky top-0 z-20 border-b border-mist-200 bg-white/90 dark:bg-navy-100/90 backdrop-blur">
    <div class="flex items-center gap-4 px-5 py-4 sm:px-8 lg:px-10">
        <button type="button" @click="menu = true"
                class="rounded-lg p-2 text-navy-900/60 transition duration-200 hover:bg-mist-100 hover:text-navy-900 active:scale-95 lg:hidden">
            <span class="sr-only">{{ __('admin.actions.open_menu') }}</span>
            <x-icon name="menu" size="20" />
        </button>

        {{-- The height is reserved for both lines whether or not this page sets a
             subheading. Without it the bar is a line shorter on some pages, and
             the whole layout jumps up and down as staff move through the menu. --}}
        <div class="flex min-h-11 min-w-0 flex-1 flex-col justify-center">
            <h1 class="truncate font-display text-lg font-bold text-navy-900">@yield('heading', __('admin.panel'))</h1>
            @hasSection('subheading')
                <p class="truncate text-xs text-navy-900/50">@yield('subheading')</p>
            @endif
        </div>

        {{-- The palette's handle. It is a keyboard feature first, so the
             shortcut is on the button rather than in a help page nobody opens;
             the modifier is corrected to ⌘ on a Mac by app.js. --}}
        <button type="button" @click="$dispatch('panel-palette')"
                class="flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-xs font-semibold
                       text-navy-900/60 transition duration-200 hover:border-teal-300 hover:bg-teal-50/60 hover:text-teal-800"
                title="{{ __('admin.palette.open') }}">
            <x-icon name="search" size="15" />
            <span class="hidden lg:inline">{{ __('admin.palette.open') }}</span>
            <span class="hidden items-center gap-1 sm:flex">
                <kbd class="kbd" data-shortcut-mod>Ctrl</kbd><kbd class="kbd">K</kbd>
            </span>
        </button>

        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="hidden items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-xs font-semibold
                  text-navy-900/70 transition duration-200 hover:border-teal-300 hover:bg-teal-50/60 hover:text-teal-800 md:inline-flex"
           title="{{ __('admin.view_site') }}">
            <x-icon name="external-link" size="15" />
            {{ __('admin.view_site') }}
        </a>

        <x-theme-toggle variant="panel" />

        <x-locale-switcher variant="drawer" class="hidden sm:flex" />
    </div>
</header>
