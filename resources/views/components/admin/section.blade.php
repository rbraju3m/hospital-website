@props(['title' => null, 'description' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'admin-card']) }}>
    @if ($title)
        <header class="border-b border-mist-200 px-5 py-4">
            <h2 class="font-display text-base font-bold text-navy-900">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-xs text-navy-900/50">{{ $description }}</p>
            @endif
        </header>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">{{ $slot }}</div>
</section>
