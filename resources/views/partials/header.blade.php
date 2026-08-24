@php
    /*
     | The primary menu is built here rather than inline so it can be filtered
     | against Site controls in one place — an area switched off in the panel
     | disappears from the header, the drawer and the overflow menu together,
     | and its route stops answering, so there is no link left pointing at a 404.
     |
     | It is also split deliberately: `primary` is what the bar shows, `more` is
     | what folds into the overflow menu. The bar has to stay on **one line** at
     | every width it is visible, and the only way to guarantee that is to cap
     | how many items can ever be in it. Six triggers is the cap; everything
     | else lives one click away rather than wrapping onto a second row.
     */
    $item = fn (string $route, string $label, ?string $feature = null, ?string $icon = null) => [
        'route' => $route,
        'label' => $label,
        'feature' => $feature,
        'icon' => $icon,
    ];

    $enabled = fn (array $items) => array_values(array_filter(
        $items,
        fn ($entry) => $entry['feature'] === null || feature($entry['feature']),
    ));

    $primary = $enabled([
        $item('doctors.index', __('nav.items.doctors'), 'area_doctors'),
        $item('services.index', __('nav.items.services'), 'area_services'),
        $item('packages.index', __('nav.items.packages'), 'area_packages'),
        $item('diagnostics.index', __('nav.items.diagnostics'), 'area_diagnostics'),
    ]);

    $more = $enabled([
        $item('posts.index', __('nav.items.posts'), 'area_posts', 'newspaper'),
        $item('portal.dashboard', __('nav.items.portal'), 'area_portal', 'user-round'),
        $item('international', __('nav.items.international'), 'area_international', 'globe'),
        $item('about', __('nav.items.about_long'), 'area_about', 'hospital'),
        $item('emergency', __('nav.footer.emergency_care'), 'area_emergency', 'ambulance'),
        $item('contact', __('nav.items.contact'), 'area_contact', 'message-circle'),
    ]);

    $showDepartments = feature('area_departments');
    $isActive = fn (string $route) => request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route);
    $moreActive = collect($more)->contains(fn ($entry) => $isActive($entry['route']));
@endphp

{{-- Thin utility bar: hotline, emergency and international desk --}}
@if (feature('chrome_topbar'))
    <div class="hidden bg-navy-950 text-white lg:block">
        <div class="shell flex h-11 items-center justify-between gap-6 overflow-hidden text-[13px] whitespace-nowrap">
            <div class="flex min-w-0 items-center gap-6">
                <span class="flex items-center gap-2 truncate text-white/65 transition duration-200 hover:text-white">
                    <x-icon name="map-pin" size="15" class="text-teal-400" />
                    {{ setting('address_line') }}, {{ setting('address_city') }}
                </span>
                <span class="hidden items-center gap-2 text-white/65 xl:flex">
                    {{-- The hospital never closes; the live dot says so without copy. --}}
                    <span class="pulse-dot text-teal-400" aria-hidden="true"></span>
                    {{ setting('opening_hours') }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-5">
                @if (feature('chrome_locale_switcher'))
                    <x-locale-switcher />
                @endif

                @if (feature('area_international'))
                    <a href="{{ route('international') }}"
                       class="link-underline hidden text-white/65 transition duration-200 hover:text-white xl:inline">
                        {{ __('nav.items.international') }}
                    </a>
                @endif

                <a href="tel:{{ setting('hotline') }}"
                   class="group/hotline flex items-center gap-2 font-semibold transition duration-200 hover:text-teal-300">
                    <x-icon name="phone" size="15" class="transition-transform duration-300 group-hover/hotline:-rotate-12" />
                    {{ __('common.hotline') }} {{ setting('hotline') }}
                </a>

                <a href="tel:{{ setting('ambulance_number') }}"
                   class="flex items-center gap-2 rounded-full bg-urgent-600 px-3.5 py-1.5 font-semibold
                          shadow-[0_0_0_0_rgb(220_38_38/0.5)] transition duration-300
                          hover:bg-urgent-700 hover:shadow-[0_0_0_4px_rgb(220_38_38/0.22)]">
                    <x-icon name="ambulance" size="15" />
                    {{ __('common.emergency') }}
                </a>
            </div>
        </div>
    </div>
@endif

{{-- The header condenses once the page scrolls: shorter bar, denser blur, a
     hairline and a shadow. `is-scrolled` is toggled by app.js. --}}
<header data-site-header
        x-data="{ open: false, mega: null }"
        @keydown.escape.window="open = false; mega = null"
        class="group sticky top-0 z-50 border-b border-transparent bg-white/85 backdrop-blur-xl
               transition-[background-color,border-color,box-shadow] duration-300 ease-out
               [&.is-scrolled]:border-mist-200 [&.is-scrolled]:bg-white/95 [&.is-scrolled]:shadow-soft">

    <div class="shell flex h-[72px] items-center gap-4 transition-[height] duration-300 ease-out
                group-[.is-scrolled]:h-[62px]">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="group/logo flex shrink-0 items-center gap-3"
           aria-label="{{ __('common.home_aria', ['name' => setting('site_name')]) }}">
            <span class="relative grid h-11 w-11 place-items-center rounded-xl bg-navy-900 text-white shadow-soft
                         transition duration-300 ease-out
                         group-hover/logo:bg-teal-600 group-hover/logo:shadow-lift
                         group-[.is-scrolled]:h-10 group-[.is-scrolled]:w-10">
                <x-icon name="heart-pulse" size="24" stroke="2"
                        class="transition-transform duration-500 ease-out group-hover/logo:scale-110" />
            </span>
            <span class="hidden leading-tight sm:block">
                <span class="block font-display text-lg font-extrabold tracking-tight whitespace-nowrap text-navy-900">
                    {{ setting('site_name') }}
                </span>
                {{-- The tagline is the first thing to go: it is the widest part
                     of the logo block and the bar has to stay on one line. --}}
                <span class="hidden max-w-[11rem] truncate text-[11px] font-medium tracking-wide text-navy-900/50 2xl:block">
                    {{ setting('site_tagline') }}
                </span>
            </span>
        </a>

        {{-- Desktop navigation. `flex-nowrap` plus a capped item count is the
             whole trick: this bar is one line or it is the drawer, never two. --}}
        <nav class="hidden min-w-0 flex-1 flex-nowrap items-center justify-center lg:flex"
             aria-label="{{ __('nav.primary') }}">

            @if ($showDepartments)
                {{-- Departments mega menu --}}
                <div class="relative shrink-0"
                     @mouseenter="mega = 'departments'" @mouseleave="mega = null">
                    @if (feature('chrome_mega_menu'))
                        <button type="button"
                                @click="mega = mega === 'departments' ? null : 'departments'"
                                :aria-expanded="mega === 'departments' ? 'true' : 'false'"
                                class="nav-link {{ request()->routeIs('departments.*') ? 'nav-link-active' : '' }}">
                            {{ __('nav.items.departments') }}
                            <x-icon name="chevron-down" size="14"
                                    ::class="mega === 'departments' && 'rotate-180'"
                                    class="transition-transform duration-300 ease-out" />
                        </button>

                        {{-- The centring translate lives on the wrapper so the panel's own
                             transform is free for the enter/leave transition. --}}
                        <div class="absolute left-1/2 top-full z-50 w-[54rem] max-w-[92vw] -translate-x-1/2 pt-3">
                            <div x-show="mega === 'departments'" x-cloak
                                 x-transition:enter="transition duration-250 ease-out"
                                 x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.985]"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition duration-150 ease-in"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.99]"
                                 class="card overflow-hidden p-2 shadow-lift">
                                <div class="grid grid-cols-2 gap-1">
                                    @foreach ($navDepartments->take(12) as $dept)
                                        <a href="{{ route('departments.show', $dept) }}"
                                           class="group/item flex items-start gap-3 rounded-xl p-3 transition duration-200 hover:bg-mist-50">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700
                                                         transition duration-300 ease-out
                                                         group-hover/item:scale-110 group-hover/item:bg-teal-600 group-hover/item:text-white">
                                                <x-icon :name="$dept->icon" size="18" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-navy-900 transition group-hover/item:text-teal-700">{{ $dept->name }}</span>
                                                <span class="mt-0.5 block truncate text-xs text-navy-900/55">{{ $dept->tagline }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ route('departments.index') }}"
                                   class="group/all mt-1 flex items-center justify-center gap-2 rounded-xl bg-navy-50 px-4 py-3
                                          text-sm font-semibold text-navy-900 transition duration-200 hover:bg-navy-900 hover:text-white">
                                    {{ __('nav.view_all_departments', ['count' => $navDepartments->count()]) }}
                                    <x-icon name="arrow-right" size="16"
                                            class="transition-transform duration-300 ease-out group-hover/all:translate-x-1" />
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('departments.index') }}"
                           class="nav-link {{ request()->routeIs('departments.*') ? 'nav-link-active' : '' }}">
                            {{ __('nav.items.departments') }}
                        </a>
                    @endif
                </div>
            @endif

            @foreach ($primary as $entry)
                <a href="{{ route($entry['route']) }}"
                   @if ($isActive($entry['route'])) aria-current="page" @endif
                   class="nav-link shrink-0 {{ $isActive($entry['route']) ? 'nav-link-active' : '' }}">
                    {{ $entry['label'] }}
                </a>
            @endforeach

            {{-- Everything that does not fit the bar, one click away. --}}
            @if ($more)
                <div class="relative shrink-0" @mouseenter="mega = 'more'" @mouseleave="mega = null">
                    <button type="button"
                            @click="mega = mega === 'more' ? null : 'more'"
                            :aria-expanded="mega === 'more' ? 'true' : 'false'"
                            class="nav-link {{ $moreActive ? 'nav-link-active' : '' }}">
                        {{ __('nav.items.more') }}
                        <x-icon name="chevron-down" size="14"
                                ::class="mega === 'more' && 'rotate-180'"
                                class="transition-transform duration-300 ease-out" />
                    </button>

                    <div class="absolute end-0 top-full z-50 w-64 pt-3">
                        <div x-show="mega === 'more'" x-cloak
                             x-transition:enter="transition duration-200 ease-out"
                             x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition duration-150 ease-in"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-[0.99]"
                             class="card overflow-hidden p-1.5 shadow-lift">
                            @foreach ($more as $entry)
                                <a href="{{ route($entry['route']) }}"
                                   class="group/more flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium
                                          transition duration-200 hover:bg-mist-50
                                          {{ $isActive($entry['route']) ? 'bg-mist-50 text-navy-900' : 'text-navy-900/75' }}">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-mist-100 text-navy-700
                                                 transition duration-300 ease-out
                                                 group-hover/more:bg-teal-600 group-hover/more:text-white">
                                        <x-icon :name="$entry['icon']" size="16" />
                                    </span>
                                    {{ $entry['label'] }}
                                    <x-icon name="chevron-right" size="14"
                                            class="ms-auto text-navy-900/25 transition-transform duration-300 ease-out group-hover/more:translate-x-0.5" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </nav>

        {{-- Actions --}}
        <div class="ms-auto flex shrink-0 items-center gap-2 lg:ms-0">
            @if (feature('chrome_header_search') && feature('area_doctors'))
                <a href="{{ route('doctors.index') }}"
                   class="btn-outline btn-sm hidden md:inline-flex"
                   aria-label="{{ __('common.find_a_doctor') }}">
                    <x-icon name="search" size="16" />
                    <span class="hidden 2xl:inline">{{ __('common.find_a_doctor') }}</span>
                </a>
            @endif

            @if (feature('chrome_header_book') && feature('area_appointment'))
                <a href="{{ route('appointment.create') }}" class="btn-accent btn-sm btn-nudge hidden whitespace-nowrap sm:inline-flex">
                    <x-icon name="calendar-check" size="16" />
                    {{ __('common.book_appointment_short') }}
                </a>
            @endif

            <button type="button" @click="open = !open"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-mist-200 text-navy-900
                           transition duration-200 hover:border-navy-200 hover:bg-mist-50 active:scale-95 lg:hidden"
                    :aria-expanded="open ? 'true' : 'false'" aria-label="{{ __('common.toggle_menu') }}">
                <x-icon name="menu" size="20" x-show="!open" />
                <x-icon name="x" size="20" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak x-collapse class="border-t border-mist-200 bg-white lg:hidden">
        <div class="shell max-h-[72vh] space-y-1 overflow-y-auto py-5">
            @if (feature('area_appointment'))
                <a href="{{ route('appointment.create') }}" class="btn-accent w-full sm:hidden">
                    <x-icon name="calendar-check" size="16" /> {{ __('common.book_appointment_short') }}
                </a>
            @endif

            @if ($showDepartments)
                <div x-data="{ expanded: false }" class="pt-1">
                    <button type="button" @click="expanded = !expanded"
                            class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold text-navy-900
                                   transition duration-200 hover:bg-mist-50">
                        {{ __('nav.items.departments') }}
                        <x-icon name="chevron-down" size="16" ::class="expanded && 'rotate-180'"
                                class="transition-transform duration-300 ease-out" />
                    </button>
                    <div x-show="expanded" x-collapse class="space-y-0.5 pb-2 pl-3">
                        @foreach ($navDepartments as $dept)
                            <a href="{{ route('departments.show', $dept) }}"
                               class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm text-navy-900/75
                                      transition duration-200 hover:bg-mist-50 hover:ps-5">
                                <x-icon :name="$dept->icon" size="16" class="text-teal-600" />
                                {{ $dept->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach (array_merge($primary, $more) as $entry)
                <a href="{{ route($entry['route']) }}"
                   class="block rounded-xl px-4 py-3 text-sm font-semibold text-navy-900
                          transition duration-200 hover:bg-mist-50 hover:ps-5">{{ $entry['label'] }}</a>
            @endforeach

            <div class="mt-3 grid gap-2 border-t border-mist-200 pt-4">
                <a href="tel:{{ setting('hotline') }}" class="btn-outline w-full">
                    <x-icon name="phone" size="16" /> {{ __('common.hotline') }} {{ setting('hotline') }}
                </a>
                <a href="tel:{{ setting('ambulance_number') }}" class="btn-urgent w-full">
                    <x-icon name="ambulance" size="16" /> {{ __('nav.emergency_ambulance') }}
                </a>

                @if (feature('chrome_locale_switcher'))
                    <x-locale-switcher variant="drawer" class="mt-1" />
                @endif
            </div>
        </div>
    </div>
</header>
