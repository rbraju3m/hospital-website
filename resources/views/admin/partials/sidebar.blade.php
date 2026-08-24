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
            ['route' => 'admin.diagnostics.index', 'match' => 'admin.diagnostics.*', 'icon' => 'microscope', 'label' => __('admin.nav.diagnostics')],
            ['route' => 'admin.posts.index', 'match' => 'admin.posts.*', 'icon' => 'newspaper', 'label' => __('admin.nav.posts')],
            ['route' => 'admin.gallery.index', 'match' => 'admin.gallery.*', 'icon' => 'image', 'label' => __('admin.nav.gallery')],
            ['route' => 'admin.testimonials.index', 'match' => 'admin.testimonials.*', 'icon' => 'quote', 'label' => __('admin.nav.testimonials')],
        ],
        'portal' => [
            ['route' => 'admin.documents.index', 'match' => 'admin.documents.*', 'icon' => 'file-text', 'label' => __('admin.nav.documents')],
            ['route' => 'admin.patients.index', 'match' => 'admin.patients.*', 'icon' => 'user-round', 'label' => __('admin.nav.patients')],
        ],
        'system' => [
            ['route' => 'admin.site.edit', 'match' => 'admin.site.*', 'icon' => 'power', 'label' => __('admin.nav.site_controls')],
            ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'sliders', 'label' => __('admin.nav.settings')],
            ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => __('admin.nav.users')],
        ],
    ];
@endphp

<div class="flex items-center justify-between px-5 py-6">
    <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-teal-500 font-display text-sm font-extrabold text-navy-950
                     transition duration-300 ease-out group-hover:scale-105 group-hover:shadow-[0_0_0_4px_rgb(23_166_152/0.2)]">
            RBR
        </span>
        <span class="font-display text-sm font-bold text-white">{{ __('admin.panel') }}</span>
    </a>

    <button type="button" @click="menu = false"
            class="rounded-lg p-1.5 text-white/60 transition duration-200 hover:rotate-90 hover:text-white lg:hidden">
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
                    {{-- Work waiting on the desk: the ring makes it findable in
                         peripheral vision without another colour in the sidebar. --}}
                    <span class="rounded-full bg-teal-500 px-2 py-0.5 text-[11px] font-bold text-navy-950
                                 shadow-[0_0_0_3px_rgb(23_166_152/0.18)]">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    @endforeach
</nav>

<div class="border-t border-white/10 px-5 py-4">
    {{-- Whether the public site is on air, always in view. A panel that looks
         identical whether or not visitors can reach the site is how a
         maintenance switch gets left on over a weekend. --}}
    <a href="{{ route('admin.site.edit') }}"
       class="mb-3 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium transition duration-200
              {{ feature('behaviour_maintenance') ? 'bg-urgent-600/15 text-urgent-100 hover:bg-urgent-600/25' : 'text-white/50 hover:bg-white/10 hover:text-white' }}">
        @if (feature('behaviour_maintenance'))
            <span class="pulse-dot text-urgent-500" aria-hidden="true"></span>
            {{ __('admin.site_status.maintenance') }}
        @else
            <span class="pulse-dot text-teal-400" aria-hidden="true"></span>
            {{ __('admin.site_status.live') }}
        @endif
    </a>

    <a href="{{ route('home') }}" target="_blank" rel="noopener"
       class="group flex items-center gap-2 px-2 text-xs font-medium text-white/50 transition duration-200 hover:text-white">
        <x-icon name="external-link" size="14"
                class="transition-transform duration-300 ease-out group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
        {{ __('admin.view_site') }}
    </a>
</div>
