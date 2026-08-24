@props(['album'])

@php
    // Upload first, then stand-in photography. A row of albums with one cover
    // and three blanks reads as broken, the same as an article grid does.
    $cover = image_url($album->untranslated('image'), 'cover', $album->id, 'gallery-album');
    $count = $album->photos_count ?? $album->photos()->count();
@endphp

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col overflow-hidden']) }}>
    <div class="relative overflow-hidden bg-mist-100">
        @if ($cover)
            <img src="{{ $cover }}" alt="" loading="lazy" data-fade
                 class="card-zoom aspect-[4/3] w-full object-cover">
            <span aria-hidden="true" class="media-scrim"></span>
        @else
            <span class="grid aspect-[4/3] w-full place-items-center text-navy-900/20">
                <x-icon name="image" size="34" />
            </span>
        @endif

        <span class="media-badge start-3 top-3">
            <x-icon name="image" size="12" />
            {{ trans_choice('gallery.photos', $count, ['count' => number_format($count)]) }}
        </span>
    </div>

    <div class="flex flex-1 flex-col p-6">
        <h3 class="font-display text-lg font-bold leading-snug text-navy-900">
            <a href="{{ route('gallery.show', $album) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                {{ $album->title }}
            </a>
        </h3>

        @if ($album->summary)
            <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $album->summary }}</p>
        @endif

        <span class="card-arrow mt-auto flex items-center gap-2 pt-5 text-sm font-semibold text-teal-700">
            {{ __('gallery.view_album') }}
            <x-icon name="arrow-right" size="16" />
        </span>
    </div>
</article>
