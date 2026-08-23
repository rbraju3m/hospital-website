{{-- Thin utility bar: hotline, emergency and international desk --}}
<div class="hidden bg-navy-900 text-white lg:block">
    <div class="shell flex h-11 items-center justify-between text-[13px]">
        <div class="flex items-center gap-6">
            <span class="flex items-center gap-2 text-white/70">
                <x-icon name="map-pin" size="15" />
                {{ setting('address_line') }}, {{ setting('address_city') }}
            </span>
            <span class="flex items-center gap-2 text-white/70">
                <x-icon name="clock" size="15" />
                {{ setting('opening_hours') }}
            </span>
        </div>

        <div class="flex items-center gap-5">
            <x-locale-switcher />

            <a href="{{ route('international') }}" class="text-white/70 transition hover:text-white">
                {{ __('nav.items.international') }}
            </a>
            <a href="tel:{{ setting('hotline') }}" class="flex items-center gap-2 font-semibold transition hover:text-teal-300">
                <x-icon name="phone" size="15" />
                {{ __('common.hotline') }} {{ setting('hotline') }}
            </a>
            <a href="tel:{{ setting('ambulance_number') }}"
               class="flex items-center gap-2 rounded-full bg-urgent-600 px-3.5 py-1.5 font-semibold transition hover:bg-urgent-700">
                <x-icon name="ambulance" size="15" />
                {{ __('common.emergency') }}
            </a>
        </div>
    </div>
</div>

<header data-site-header
        x-data="{ open: false, mega: null }"
        @keydown.escape.window="open = false; mega = null"
        class="sticky top-0 z-50 border-b border-transparent bg-white/95 backdrop-blur transition-all duration-300
               [&.is-scrolled]:border-mist-200 [&.is-scrolled]:shadow-soft">

    <div class="shell flex h-[72px] items-center justify-between gap-6">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3"
           aria-label="{{ __('common.home_aria', ['name' => setting('site_name')]) }}">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-navy-900 text-white shadow-soft">
                <x-icon name="heart-pulse" size="24" stroke="2" />
            </span>
            <span class="leading-tight">
                <span class="block font-display text-lg font-extrabold tracking-tight text-navy-900">{{ setting('site_name') }}</span>
                <span class="block text-[11px] font-medium tracking-wide text-navy-900/50">{{ setting('site_tagline') }}</span>
            </span>
        </a>

        {{-- Desktop navigation --}}
        <nav class="hidden items-center gap-1 xl:flex" aria-label="{{ __('nav.primary') }}">
            {{-- Departments mega menu --}}
            <div class="relative" @mouseenter="mega = 'departments'" @mouseleave="mega = null">
                <button type="button"
                        @click="mega = mega === 'departments' ? null : 'departments'"
                        :aria-expanded="mega === 'departments' ? 'true' : 'false'"
                        class="flex items-center gap-1.5 rounded-full px-4 py-2.5 text-sm font-medium text-navy-900/75
                               transition hover:bg-navy-50 hover:text-navy-900
                               {{ request()->routeIs('departments.*') ? 'bg-navy-50 text-navy-900' : '' }}">
                    {{ __('nav.items.departments') }}
                    <x-icon name="chevron-down" size="15" ::class="mega === 'departments' && 'rotate-180'" class="transition-transform" />
                </button>

                <div x-show="mega === 'departments'" x-cloak x-transition.opacity.duration.150ms
                     class="absolute left-1/2 top-full z-50 w-[52rem] -translate-x-1/2 pt-3">
                    <div class="card overflow-hidden p-2 shadow-lift">
                        <div class="grid grid-cols-2 gap-1">
                            @foreach ($navDepartments->take(12) as $dept)
                                <a href="{{ route('departments.show', $dept) }}"
                                   class="group flex items-start gap-3 rounded-xl p-3 transition hover:bg-mist-50">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700
                                                 transition group-hover:bg-teal-600 group-hover:text-white">
                                        <x-icon :name="$dept->icon" size="18" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-navy-900">{{ $dept->name }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-navy-900/55">{{ $dept->tagline }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                        <a href="{{ route('departments.index') }}"
                           class="mt-1 flex items-center justify-center gap-2 rounded-xl bg-navy-50 px-4 py-3
                                  text-sm font-semibold text-navy-900 transition hover:bg-navy-100">
                            {{ __('nav.view_all_departments', ['count' => $navDepartments->count()]) }}
                            <x-icon name="arrow-right" size="16" />
                        </a>
                    </div>
                </div>
            </div>

            @foreach ([
                ['doctors.index', __('nav.items.doctors')],
                ['services.index', __('nav.items.services')],
                ['packages.index', __('nav.items.packages')],
                ['posts.index', __('nav.items.posts')],
                ['about', __('nav.items.about')],
                ['contact', __('nav.items.contact')],
            ] as [$route, $label])
                <a href="{{ route($route) }}"
                   class="rounded-full px-4 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route)
                             ? 'bg-navy-50 text-navy-900' : 'text-navy-900/75 hover:bg-navy-50 hover:text-navy-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('doctors.index') }}" class="btn-outline btn-sm hidden md:inline-flex">
                <x-icon name="search" size="16" />
                {{ __('common.find_a_doctor') }}
            </a>
            <a href="{{ route('appointment.create') }}" class="btn-accent btn-sm hidden sm:inline-flex">
                <x-icon name="calendar-check" size="16" />
                {{ __('common.book_appointment_short') }}
            </a>

            <button type="button" @click="open = !open"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-mist-200 text-navy-900 xl:hidden"
                    :aria-expanded="open ? 'true' : 'false'" aria-label="{{ __('common.toggle_menu') }}">
                <x-icon name="menu" size="20" x-show="!open" />
                <x-icon name="x" size="20" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak x-collapse class="border-t border-mist-200 bg-white xl:hidden">
        <div class="shell max-h-[70vh] space-y-1 overflow-y-auto py-5">
            <a href="{{ route('appointment.create') }}" class="btn-accent w-full sm:hidden">
                <x-icon name="calendar-check" size="16" /> {{ __('common.book_appointment_short') }}
            </a>

            <div x-data="{ expanded: false }" class="pt-1">
                <button type="button" @click="expanded = !expanded"
                        class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold text-navy-900 hover:bg-mist-50">
                    {{ __('nav.items.departments') }}
                    <x-icon name="chevron-down" size="16" ::class="expanded && 'rotate-180'" class="transition-transform" />
                </button>
                <div x-show="expanded" x-collapse class="space-y-0.5 pb-2 pl-3">
                    @foreach ($navDepartments as $dept)
                        <a href="{{ route('departments.show', $dept) }}"
                           class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm text-navy-900/75 hover:bg-mist-50">
                            <x-icon :name="$dept->icon" size="16" class="text-teal-600" />
                            {{ $dept->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            @foreach ([
                ['doctors.index', __('nav.items.doctors')],
                ['services.index', __('nav.items.services')],
                ['packages.index', __('nav.items.packages')],
                ['posts.index', __('nav.items.posts')],
                ['international', __('nav.items.international')],
                ['about', __('nav.items.about_long')],
                ['contact', __('nav.items.contact')],
            ] as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block rounded-xl px-4 py-3 text-sm font-semibold text-navy-900 hover:bg-mist-50">{{ $label }}</a>
            @endforeach

            <div class="mt-3 grid gap-2 border-t border-mist-200 pt-4">
                <a href="tel:{{ setting('hotline') }}" class="btn-outline w-full">
                    <x-icon name="phone" size="16" /> {{ __('common.hotline') }} {{ setting('hotline') }}
                </a>
                <a href="tel:{{ setting('ambulance_number') }}" class="btn-urgent w-full">
                    <x-icon name="ambulance" size="16" /> {{ __('nav.emergency_ambulance') }}
                </a>

                <x-locale-switcher variant="drawer" class="mt-1" />
            </div>
        </div>
    </div>
</header>
