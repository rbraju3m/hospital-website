@props(['message', 'action' => null, 'href' => null, 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'px-6 py-16 text-center']) }}>
    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-navy-900/35">
        <x-icon :name="$icon" size="22" />
    </span>
    <p class="mt-4 text-sm text-navy-900/50">{{ $message }}</p>

    @if ($action && $href)
        <a href="{{ $href }}" class="btn-primary btn-sm mt-5">
            <x-icon name="plus" size="15" />
            {{ $action }}
        </a>
    @endif
</div>
