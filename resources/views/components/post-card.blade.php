@props(['post'])

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col p-7']) }}>
    <div class="flex items-center gap-3 text-xs">
        <span class="chip-accent">{{ category_label('posts', $post->category) }}</span>
        <span class="text-navy-900/45">{{ __('common.read_time', ['count' => $post->read_minutes]) }}</span>
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
</article>
