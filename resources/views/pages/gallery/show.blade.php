@extends('layouts.site')

@php
    /* One list drives both the grid and the lightbox, so a tile and the slide it
       opens can never drift apart. Photos with nothing to show — no upload, and
       stand-in imagery switched off — are dropped here rather than rendered as
       an empty frame. */
    $slides = $photos
        ->map(fn ($photo) => ['src' => $photo->url(), 'caption' => $photo->caption])
        ->filter(fn ($slide) => filled($slide['src']))
        ->values();
@endphp

@section('title', $album->title)
@section('meta_description', $album->summary ?: __('gallery.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('gallery.index.eyebrow')"
    :title="$album->title"
    :lede="$album->summary"
    :crumbs="[__('gallery.index.crumb') => route('gallery.index'), $album->title => null]" />

<section class="section">
    <div class="shell">
        @if ($album->description)
            <div class="mx-auto mb-12 max-w-3xl space-y-5 text-center text-base leading-relaxed text-navy-900/70 reveal">
                @foreach (preg_split('/\n+/', $album->description) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif

        @if ($slides->isEmpty())
            <div class="card p-14 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-mist-100 text-navy-900/30">
                    <x-icon name="image" size="26" />
                </span>
                <p class="mt-5 text-navy-900/60">{{ __('gallery.album.empty') }}</p>
                <a href="{{ route('gallery.index') }}" class="btn-outline btn-sm mt-6">
                    <x-icon name="arrow-left" size="16" />
                    {{ __('gallery.album.back') }}
                </a>
            </div>
        @else
            <div x-data="galleryLightbox(@js($slides))">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4" data-reveal-stagger="50">
                    @foreach ($slides as $slide)
                        <button type="button"
                                @click="open({{ $loop->index }}, $event.currentTarget)"
                                class="media-frame reveal reveal-clip group/tile relative block aspect-[4/3] w-full
                                       focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500"
                                aria-label="{{ $slide['caption'] ?: __('gallery.lightbox.open') }}">
                            <img src="{{ $slide['src'] }}" alt="{{ $slide['caption'] }}" loading="lazy" data-fade
                                 class="h-full w-full object-cover">

                            <span aria-hidden="true"
                                  class="absolute inset-0 grid place-items-center bg-navy-950/0 text-white/0
                                         transition duration-300 ease-out
                                         group-hover/tile:bg-navy-950/35 group-hover/tile:text-white">
                                <x-icon name="maximize" size="22" />
                            </span>

                            @if ($slide['caption'])
                                <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-navy-950/80 to-transparent
                                             px-3 pb-2.5 pt-8 text-start text-xs font-medium text-white">
                                    {{ $slide['caption'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Lightbox. Escape, the arrow keys and a swipe all move it; the
                     close button takes focus on open and hands it back to the
                     tile that opened it on close. --}}
                <div x-show="index !== null" x-cloak
                     x-transition:enter="transition duration-200 ease-out"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition duration-150 ease-in"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @keydown.escape.window="close()"
                     @keydown.arrow-right.window="next()"
                     @keydown.arrow-left.window="previous()"
                     @touchstart="touchStart($event)" @touchend="touchEnd($event)"
                     role="dialog" aria-modal="true" aria-label="{{ $album->title }}"
                     class="fixed inset-0 z-[60] flex flex-col bg-navy-950/95 backdrop-blur-sm">

                    <div class="flex items-center justify-between gap-4 px-5 py-4 text-white sm:px-8">
                        <p class="text-sm font-medium text-white/60">
                            <span x-text="index + 1"></span> / {{ $slides->count() }}
                        </p>

                        <button type="button" x-ref="close" @click="close()"
                                class="grid h-11 w-11 place-items-center rounded-xl text-white/70 transition duration-200
                                       hover:rotate-90 hover:bg-white/10 hover:text-white">
                            <span class="sr-only">{{ __('gallery.lightbox.close') }}</span>
                            <x-icon name="x" size="22" />
                        </button>
                    </div>

                    <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16">
                        @if ($slides->count() > 1)
                            <button type="button" @click="previous()"
                                    class="absolute start-2 top-1/2 z-10 grid h-12 w-12 -translate-y-1/2 place-items-center
                                           rounded-full bg-white/10 text-white transition duration-200
                                           hover:bg-white/20 active:scale-95 sm:start-4">
                                <span class="sr-only">{{ __('gallery.lightbox.previous') }}</span>
                                <x-icon name="chevron-left" size="22" />
                            </button>

                            <button type="button" @click="next()"
                                    class="absolute end-2 top-1/2 z-10 grid h-12 w-12 -translate-y-1/2 place-items-center
                                           rounded-full bg-white/10 text-white transition duration-200
                                           hover:bg-white/20 active:scale-95 sm:end-4">
                                <span class="sr-only">{{ __('gallery.lightbox.next') }}</span>
                                <x-icon name="chevron-right" size="22" />
                            </button>
                        @endif

                        <figure class="flex max-h-full min-h-0 flex-col items-center gap-4">
                            <img :src="slide?.src" :alt="slide?.caption ?? ''"
                                 class="max-h-[76vh] w-auto max-w-full rounded-xl object-contain shadow-lift">
                            <figcaption x-show="slide?.caption" x-text="slide?.caption"
                                        class="max-w-2xl text-center text-sm text-white/70"></figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

@if ($more->isNotEmpty())
<section class="section bg-mist-50 pt-0">
    <div class="shell">
        <x-section-heading
            :title="__('gallery.album.more')"
            :link="route('gallery.index')"
            :link-label="__('gallery.album.all')"
            class="reveal" />

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="70">
            @foreach ($more as $other)
                <x-album-card :album="$other" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('galleryLightbox', (slides) => ({
            slides,
            index: null,
            trigger: null,
            touchX: null,

            get slide() {
                return this.index === null ? null : this.slides[this.index];
            },

            open(index, trigger = null) {
                this.index = index;
                this.trigger = trigger;
                // The page behind must not scroll under the overlay.
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => this.$refs.close?.focus());
                this.preload(index + 1);
            },

            close() {
                if (this.index === null) return;

                this.index = null;
                document.body.classList.remove('overflow-hidden');
                // Hand focus back to the tile that opened it, or the keyboard
                // user is returned to the top of the document.
                this.trigger?.focus();
                this.trigger = null;
            },

            next() {
                if (this.index === null) return;

                this.index = (this.index + 1) % this.slides.length;
                this.preload(this.index + 1);
            },

            previous() {
                if (this.index === null) return;

                this.index = (this.index - 1 + this.slides.length) % this.slides.length;
                this.preload(this.index - 1);
            },

            // Fetch the neighbour while this one is being looked at, so moving
            // through an album does not flash an empty frame on a slow line.
            preload(index) {
                const slide = this.slides[(index + this.slides.length) % this.slides.length];

                if (slide) {
                    new Image().src = slide.src;
                }
            },

            touchStart(event) {
                this.touchX = event.changedTouches[0]?.clientX ?? null;
            },

            touchEnd(event) {
                if (this.touchX === null) return;

                const delta = (event.changedTouches[0]?.clientX ?? this.touchX) - this.touchX;
                this.touchX = null;

                if (Math.abs(delta) < 40) return;

                delta < 0 ? this.next() : this.previous();
            },
        }));
    });
</script>
@endpush
