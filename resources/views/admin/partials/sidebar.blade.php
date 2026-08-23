@php
    // Grouped so the front desk's daily work sits above the editorial tools it
    // rarely touches. `match` keeps the active state on nested routes too.
    $groups = [
        null => [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => __('admin.nav.dashboard')],
            ['route' => 'admin.appointments.index', 'match' => 'admin.appointments.*', 'icon' => 'calendar-check', 'label' => __('admin.nav.appointments'), 'badge' => $pendingAppointments ?? null],
            ['route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'icon' => 'inbox', 'label' => __('admin.nav.messages'), 'badge' => $unreadMessages ?? null],
        ],
        'content' => [
            ['route' => 'admin.departments.index', 'match' => 'admin.departments.*', 'icon' => 'building', 'label' => __('admin.nav.departments')],
            ['route' => 'admin.doctors.index', 'match' => 'admin.doctors.*', 'icon' => 'stethoscope', 'label' => __('admin.nav.doctors')],
            ['route' => 'admin.services.index', 'match' => 'admin.services.*', 'icon' => 'activity', 'label' => __('admin.nav.services')],
            ['route' => 'admin.packages.index', 'match' => 'admin.packages.*', 'icon' => 'package', 'label' => __('admin.nav.packages')],
            ['route' => 'admin.posts.index', 'match' => 'admin.posts.*', 'icon' => 'newspaper', 'label' => __('admin.nav.posts')],
            ['route' => 'admin.testimonials.index', 'match' => 'admin.testimonials.*', 'icon' => 'quote', 'label' => __('admin.nav.testimonials')],
        ],
        'system' => [
            ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'sliders', 'label' => __('admin.nav.settings')],
            ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => __('admin.nav.users')],
        ],
    ];
@endphp

<div class="flex items-center justify-between px-5 py-6">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-teal-500 font-display text-sm font-extrabold text-navy-950">
            RBR
        </span>
        <span class="font-display text-sm font-bold text-white">{{ __('admin.panel') }}</span>
    </a>

    <button type="button" @click="menu = false" class="rounded-lg p-1.5 text-white/60 hover:text-white lg:hidden">
        <span class="sr-only">{{ __('admin.actions.close') }}</span>
        <x-icon name="x" size="20" />
    </button>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-6">
    @foreach ($groups as $heading => $items)
        @if ($heading)
            <p class="admin-nav-heading">{{ __("admin.nav.group_{$heading}") }}</p>
        @endif

        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="{{ request()->routeIs($item['match']) ? 'admin-nav-item-active' : 'admin-nav-item' }}">
                <x-icon :name="$item['icon']" size="18" />
                <span class="flex-1">{{ $item['label'] }}</span>

                @if (! empty($item['badge']))
                    <span class="rounded-full bg-teal-500 px-2 py-0.5 text-[11px] font-bold text-navy-950">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    @endforeach
</nav>

<div class="border-t border-white/10 px-5 py-4">
    <a href="{{ route('home') }}" target="_blank" rel="noopener"
       class="flex items-center gap-2 text-xs font-medium text-white/50 transition hover:text-white">
        <x-icon name="external-link" size="14" />
        {{ __('admin.view_site') }}
    </a>
</div>
