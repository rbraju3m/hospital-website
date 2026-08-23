@php
    $links = [
        ['portal.dashboard', 'layout-dashboard', __('portal.nav.dashboard')],
        ['portal.appointments', 'calendar-check', __('portal.nav.appointments')],
        ['portal.documents', 'file-text', __('portal.nav.documents')],
        ['portal.profile', 'user-round', __('portal.nav.profile')],
    ];
    $patient = auth('patient')->user();
@endphp

<header class="border-b border-mist-200 bg-white">
    <div class="shell flex items-center gap-4 py-4">
        <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-navy-900 font-display text-xs font-extrabold text-white">
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
               @class([
                   'flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition',
                   'border-teal-600 text-navy-900' => request()->routeIs($route),
                   'border-transparent text-navy-900/55 hover:text-navy-900' => ! request()->routeIs($route),
               ])>
                <x-icon :name="$icon" size="16" />
                {{ $label }}
            </a>
        @endforeach
    </nav>
</header>
