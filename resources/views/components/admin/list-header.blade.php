@props([
    'createHref' => null,
    'createLabel' => null,
    'placeholder' => null,
    'search' => true,
])

<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
        {{-- Carry the other filters through so searching does not reset them. --}}
        @foreach (request()->except(['q', 'page']) as $key => $value)
            @if (! is_array($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        @if ($search)
            <label class="relative min-w-0 flex-1 sm:max-w-xs">
                <span class="sr-only">{{ __('admin.actions.search') }}</span>
                <x-icon name="search" size="16" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-navy-900/35" />
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ $placeholder ?? __('admin.actions.search') }}"
                       class="input input-sm ps-9">
            </label>
        @endif

        {{ $slot }}

        <button type="submit" class="btn-outline btn-sm">{{ __('admin.actions.filter') }}</button>

        @if (request()->hasAny(['q', 'status', 'date', 'doctor', 'category', 'unread', 'untranslated', 'active']))
            <a href="{{ url()->current() }}" class="btn-ghost btn-sm">{{ __('admin.actions.reset') }}</a>
        @endif
    </form>

    @if ($createHref)
        <a href="{{ $createHref }}" class="btn-primary btn-sm">
            <x-icon name="plus" size="15" stroke="2.5" />
            {{ $createLabel ?? __('admin.actions.create') }}
        </a>
    @endif
</div>
