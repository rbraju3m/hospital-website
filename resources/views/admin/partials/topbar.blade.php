<header class="sticky top-0 z-20 border-b border-mist-200 bg-white/90 backdrop-blur">
    <div class="flex items-center gap-4 px-5 py-4 sm:px-8 lg:px-10">
        <button type="button" @click="menu = true"
                class="rounded-lg p-2 text-navy-900/60 transition duration-200 hover:bg-mist-100 hover:text-navy-900 active:scale-95 lg:hidden">
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
                    class="group flex items-center gap-2 rounded-xl px-2 py-1.5 transition duration-200 hover:bg-mist-100">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-navy-900 text-xs font-bold text-white
                             transition duration-300 ease-out group-hover:scale-105">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
                </span>
                <span class="hidden text-sm font-medium text-navy-900 sm:block">{{ auth()->user()->name }}</span>
                <x-icon name="chevron-down" size="16" ::class="open && 'rotate-180'"
                        class="text-navy-900/40 transition-transform duration-300 ease-out" />
            </button>

            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition duration-150 ease-in"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute end-0 z-30 mt-2 w-56 origin-top-right overflow-hidden rounded-xl border border-mist-200 bg-white shadow-lift">
                <div class="border-b border-mist-200 px-4 py-3">
                    <p class="truncate text-sm font-semibold text-navy-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-navy-900/50">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('admin.users.edit', auth()->user()) }}"
                   class="block px-4 py-2.5 text-sm text-navy-900/75 transition duration-150 hover:bg-mist-50 hover:ps-5">
                    {{ __('admin.nav.my_account') }}
                </a>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-2 px-4 py-2.5 text-start text-sm text-navy-900/75
                                   transition duration-150 hover:bg-mist-50 hover:ps-5">
                        <x-icon name="log-out" size="15" />
                        {{ __('admin.auth.sign_out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
