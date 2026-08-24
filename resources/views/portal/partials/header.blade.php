@php
    $links = [
        ['portal.dashboard', 'layout-dashboard', __('portal.nav.dashboard')],
        ['portal.appointments', 'calendar-check', __('portal.nav.appointments')],
        ['portal.documents', 'file-text', __('portal.nav.documents')],
        ['portal.profile', 'user-round', __('portal.nav.profile')],
    ];
    $patient = auth('patient')->user();
@endphp

<header class="sticky top-0 z-30 border-b border-mist-200 bg-white/90 dark:bg-navy-100/90 backdrop-blur-xl">
    <div class="shell flex items-center gap-4 py-4">
        <a href="{{ route('portal.dashboard') }}" class="group flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-navy-900 dark:bg-navy-100 font-display text-xs font-extrabold text-white
                         transition duration-300 ease-out group-hover:scale-105 group-hover:bg-teal-600">
                RBR
            </span>
            <span class="hidden sm:block">
                <span class="block font-display text-sm font-bold leading-tight text-navy-900">
                    {{ setting('site_name', 'RBR Hospital') }}
                </span>
                <span class="block text-[11px] text-navy-900/50">{{ __('portal.name') }}</span>
            </span>
        </a>

        <div class="ms-auto flex items-center gap-3">
            <x-locale-switcher variant="drawer" class="hidden !border-0 !p-0 sm:flex" />

            <span class="hidden text-sm text-navy-900/60 md:block">{{ $patient->name }}</span>

            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit" class="btn-outline btn-sm">
                    <x-icon name="log-out" size="15" />
                    <span class="hidden sm:inline">{{ __('admin.auth.sign_out') }}</span>
                </button>
            </form>
        </div>
    </div>

    <nav class="shell no-scrollbar -mb-px flex gap-1 overflow-x-auto">
        @foreach ($links as [$route, $icon, $label])
            <a href="{{ route($route) }}"
               @if (request()->routeIs($route)) aria-current="page" @endif
               class="{{ request()->routeIs($route) ? 'portal-tab-active' : 'portal-tab' }}">
                <x-icon :name="$icon" size="16" />
                {{ $label }}
            </a>
        @endforeach
    </nav>
</header>
