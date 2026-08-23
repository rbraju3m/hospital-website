<header class="sticky top-0 z-20 border-b border-mist-200 bg-white/90 backdrop-blur">
    <div class="flex items-center gap-4 px-5 py-4 sm:px-8 lg:px-10">
        <button type="button" @click="menu = true" class="rounded-lg p-2 text-navy-900/60 hover:bg-mist-100 lg:hidden">
            <span class="sr-only">{{ __('admin.actions.open_menu') }}</span>
            <x-icon name="menu" size="20" />
        </button>

        <div class="min-w-0 flex-1">
            <h1 class="truncate font-display text-lg font-bold text-navy-900">@yield('heading', __('admin.panel'))</h1>
            @hasSection('subheading')
                <p class="truncate text-xs text-navy-900/50">@yield('subheading')</p>
            @endif
        </div>

        <x-locale-switcher variant="drawer" class="hidden sm:flex" />

        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = ! open" @click.outside="open = false"
                    class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-mist-100">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-navy-900 text-xs font-bold text-white">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
                </span>
                <span class="hidden text-sm font-medium text-navy-900 sm:block">{{ auth()->user()->name }}</span>
                <x-icon name="chevron-down" size="16" class="text-navy-900/40" />
            </button>

            <div x-show="open" x-cloak x-transition.opacity
                 class="absolute end-0 mt-2 w-56 overflow-hidden rounded-xl border border-mist-200 bg-white shadow-lift">
                <div class="border-b border-mist-200 px-4 py-3">
                    <p class="truncate text-sm font-semibold text-navy-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-navy-900/50">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('admin.users.edit', auth()->user()) }}"
                   class="block px-4 py-2.5 text-sm text-navy-900/75 hover:bg-mist-50">
                    {{ __('admin.nav.my_account') }}
                </a>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-start text-sm text-navy-900/75 hover:bg-mist-50">
                        <x-icon name="log-out" size="15" />
                        {{ __('admin.auth.sign_out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
