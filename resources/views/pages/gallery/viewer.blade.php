{{-- The viewer itself: full-bleed, dark, and the only thing the keyboard talks
     to while it is open. Rendered inside the album's `galleryLightbox` scope. --}}
<div x-show="isOpen" x-cloak x-ref="dialog"
     x-transition:enter="transition duration-200 ease-out"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition duration-150 ease-in"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @touchstart="touchStart($event)" @touchend="touchEnd($event)"
     role="dialog" aria-modal="true" aria-label="{{ $album->title }}"
     class="fixed inset-0 z-[60] flex flex-col bg-navy-950/95 dark:bg-navy-50/95 backdrop-blur-sm">

    <div class="flex items-center justify-between gap-3 px-4 py-3 text-white sm:px-6">
        <p class="text-sm font-medium tabular-nums text-white/60">
            <span x-text="index + 1"></span> / {{ $slides->count() }}
        </p>

        <div class="flex items-center gap-1">
            <button type="button" @click="toggleFullscreen()"
                    class="grid h-11 w-11 place-items-center rounded-xl text-white/70 transition duration-200
                           hover:bg-white/10 hover:text-white"
                    :aria-pressed="fullscreen"
                    :title="fullscreen
                        ? '{{ __('gallery.lightbox.exit_fullscreen') }}'
                        : '{{ __('gallery.lightbox.fullscreen') }}'">
                <span class="sr-only">{{ __('gallery.lightbox.fullscreen') }}</span>
                <span x-show="! fullscreen"><x-icon name="maximize" size="20" /></span>
                <span x-show="fullscreen" x-cloak><x-icon name="minimize" size="20" /></span>
            </button>

            <button type="button" x-ref="close" @click="close()"
                    class="grid h-11 w-11 place-items-center rounded-xl text-white/70 transition duration-200
                           hover:rotate-90 hover:bg-white/10 hover:text-white">
                <span class="sr-only">{{ __('gallery.lightbox.close') }}</span>
                <x-icon name="x" size="22" />
            </button>
        </div>
    </div>

    <div class="relative flex min-h-0 flex-1 items-center justify-center px-3 sm:px-16">
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

        {{-- The frame clips the magnified picture rather than letting it push
             the layout around; the zoom itself is a transform on the image. --}}
        <figure class="flex h-full min-h-0 w-full flex-col items-center justify-center gap-3">
            <div class="flex min-h-0 flex-1 items-center justify-center overflow-hidden">
                <img :src="slide?.src" :alt="slide?.caption ?? ''"
                     @click="toggleZoom($event)"
                     :style="'transform-origin: ' + origin"
                     :class="zoomed ? 'scale-[2.2] cursor-zoom-out' : 'cursor-zoom-in'"
                     class="max-h-full w-auto max-w-full rounded-lg object-contain shadow-lift
                            transition-transform duration-300 ease-out">
            </div>

            <figcaption x-show="slide?.caption" x-cloak x-text="slide?.caption"
                        class="max-w-3xl px-4 text-center text-sm text-white/70"></figcaption>
        </figure>
    </div>

    @if ($slides->count() > 1)
        <div x-ref="thumbs" class="flex shrink-0 gap-2 overflow-x-auto px-4 py-3 sm:px-6">
            @foreach ($slides as $slide)
                <button type="button" @click="go({{ $loop->index }})"
                        :data-current="index === {{ $loop->index }}"
                        :class="index === {{ $loop->index }}
                            ? 'ring-2 ring-teal-400 opacity-100'
                            : 'opacity-45 hover:opacity-90'"
                        class="h-14 w-20 shrink-0 overflow-hidden rounded-lg transition duration-200"
                        aria-label="{{ $slide['caption'] ?: __('gallery.lightbox.open') }}">
                    <img src="{{ $slide['src'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                </button>
            @endforeach
        </div>
    @endif
</div>
