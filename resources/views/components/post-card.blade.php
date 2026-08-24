@props(['post'])

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col overflow-hidden']) }}>
    {{-- Upload first, then stand-in photography seeded by the slug. An article
         grid with one cover and two blanks reads as broken. --}}
    @php $cover = image_url($post->untranslated('image'), 'cover', $post->id, 'post'); @endphp

    @if ($cover)
        <div class="relative overflow-hidden bg-mist-100">
            <img src="{{ $cover }}" alt="" loading="lazy" data-fade
                 class="card-zoom aspect-[16/9] w-full object-cover">
            <span class="media-badge start-3 top-3">
                {{ category_label('posts', $post->category) }}
            </span>
        </div>
    @endif

    <div class="flex flex-1 flex-col p-7">
        <div class="flex items-center gap-3 text-xs">
            @unless ($cover)
                <span class="chip-accent">{{ category_label('posts', $post->category) }}</span>
            @endunless
            <span class="flex items-center gap-1.5 text-navy-900/45">
                <x-icon name="clock" size="13" />
                {{ __('common.read_time', ['count' => $post->read_minutes]) }}
            </span>
        </div>

        <h3 class="mt-4 font-display text-lg font-bold leading-snug text-navy-900">
            <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                {{ $post->title }}
            </a>
        </h3>

        <p class="mt-3 text-sm leading-relaxed text-navy-900/60">{{ $post->excerpt }}</p>

        <div class="mt-auto flex items-center justify-between gap-3 border-t border-mist-200 pt-5 text-xs">
            <span class="font-medium text-navy-900/70">{{ $post->author }}</span>
            <time datetime="{{ $post->published_at->toDateString() }}" class="text-navy-900/45">
                {{ $post->published_at->translatedFormat('j M Y') }}
            </time>
        </div>
    </div>
</article>
