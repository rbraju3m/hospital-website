@props([
    'eyebrow' => null,
    'title',
    'lede' => null,
    'align' => 'left',
    'link' => null,
    'linkLabel' => 'View all',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="{{ $align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl' }}">
        @if ($eyebrow)
            <p class="eyebrow">
                <span class="h-px w-6 bg-teal-500"></span>
                {{ $eyebrow }}
            </p>
        @endif

        <h2 class="h-section mt-3">{{ $title }}</h2>

        @if ($lede)
            <p class="lede mt-4">{{ $lede }}</p>
        @endif
    </div>

    @if ($link)
        <a href="{{ $link }}" class="btn-outline shrink-0 self-start sm:self-auto">
            {{ $linkLabel }}
            <x-icon name="arrow-right" size="16" />
        </a>
    @endif
</div>
