{{-- The slider hero.

     Content first, animation second: every slide is in the markup and the
     first one is visible with no JavaScript at all — `html:not(.has-js)` hides
     the rest and the strip becomes one static hero rather than an empty box or
     eleven panels stacked down the page.

     Autoplay stops on hover, on focus, when the tab is hidden, and whenever
     the visitor's device — or the Site controls motion switch — asks for less
     motion. A carousel nobody can stop is the complaint everybody has about
     carousels. --}}
<section class="hero-slider relative overflow-hidden bg-navy-900 dark:bg-navy-100"
         x-data="heroSlider({{ $slides->count() }})"
         @mouseenter="pause()" @mouseleave="resume()"
         @focusin="pause()" @focusout="resume()"
         @keydown.left.prevent="go(index - 1)" @keydown.right.prevent="go(index + 1)"
         role="region" aria-roledescription="carousel" aria-label="{{ __('home.slider.label') }}">

    @foreach ($slides as $position => $slide)
        <div class="hero-slide" :class="{ 'is-current': index === {{ $position }} }"
             @if ($position > 0) data-slide-hidden @endif
             role="group" aria-roledescription="slide"
             aria-label="{{ __('home.slider.position', ['position' => $position + 1, 'total' => $slides->count()]) }}"
             :aria-hidden="index !== {{ $position }}">

            @if ($slide->url())
                <div aria-hidden="true" class="absolute inset-0">
                    <img src="{{ $slide->url() }}" alt=""
                         @class(['h-full w-full object-cover object-center opacity-[0.55]', 'ken-burns' => $position === 0])
                         @if ($position > 0) loading="lazy" @endif>
                    <div class="absolute inset-0 bg-gradient-to-r from-navy-950 dark:from-navy-50 via-navy-950/85 dark:via-navy-50/85 to-navy-950/30 dark:to-navy-50/30"></div>
                </div>
            @endif

            <div aria-hidden="true" class="hero-grid opacity-[0.12]"></div>

            <div class="shell relative flex min-h-[26rem] items-center py-16 lg:min-h-[34rem] lg:py-24">
                <div class="max-w-2xl">
                    @if ($slide->eyebrow)
                        <p class="eyebrow anim-fade-up text-teal-300">{{ $slide->eyebrow }}</p>
                    @endif

                    {{-- One h1 on the page, on the slide that is showing when it
                         loads. The rest are paragraphs wearing the same size:
                         they are alternates of the same heading, not sections
                         of the document, and three h1s would say the page is
                         about three things. --}}
                    <{{ $position === 0 ? 'h1' : 'p' }}
                        class="anim-fade-up mt-3 font-display text-4xl font-extrabold leading-[1.05] text-white sm:text-5xl lg:text-6xl"
                        style="--anim-delay:70ms">
                        {{ $slide->title }}
                    </{{ $position === 0 ? 'h1' : 'p' }}>

                    @if ($slide->subtitle)
                        <p class="anim-fade-up mt-5 max-w-xl text-lg leading-relaxed text-white/75" style="--anim-delay:140ms">
                            {{ $slide->subtitle }}
                        </p>
                    @endif

                    @if ($slide->cta_label || $slide->cta_secondary_label)
                        <div class="anim-fade-up mt-8 flex flex-wrap gap-3" style="--anim-delay:210ms">
                            @if ($slide->cta_label && $slide->cta_url)
                                <a href="{{ $slide->cta_url }}" class="btn-accent btn-lg btn-nudge">
                                    {{ $slide->cta_label }}
                                    <x-icon name="arrow-right" size="17" />
                                </a>
                            @endif

                            @if ($slide->cta_secondary_label && $slide->cta_secondary_url)
                                <a href="{{ $slide->cta_secondary_url }}"
                                   class="btn btn-lg border border-white/25 text-white transition hover:border-white/50 hover:bg-white/10">
                                    {{ $slide->cta_secondary_label }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- Controls last in the DOM so a screen reader meets the slide before the
         machinery, and hidden entirely when there is only one panel. --}}
    @if ($slides->count() > 1)
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10">
            <div class="shell pointer-events-auto flex items-center justify-between gap-4 pb-6">
                <div class="flex items-center gap-2" role="tablist" aria-label="{{ __('home.slider.choose') }}">
                    @foreach ($slides as $position => $slide)
                        <button type="button" role="tab" @click="go({{ $position }})"
                                :aria-selected="index === {{ $position }}"
                                :class="index === {{ $position }} ? 'w-8 bg-teal-400' : 'w-2.5 bg-white/40 hover:bg-white/70'"
                                class="h-2.5 rounded-full transition-all duration-300 ease-out">
                            <span class="sr-only">{{ __('home.slider.position', ['position' => $position + 1, 'total' => $slides->count()]) }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="go(index - 1)"
                            class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white
                                   backdrop-blur transition duration-200 hover:bg-white/20 active:scale-95">
                        <span class="sr-only">{{ __('home.slider.previous') }}</span>
                        <x-icon name="chevron-left" size="18" />
                    </button>

                    <button type="button" @click="go(index + 1)"
                            class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white
                                   backdrop-blur transition duration-200 hover:bg-white/20 active:scale-95">
                        <span class="sr-only">{{ __('home.slider.next') }}</span>
                        <x-icon name="chevron-right" size="18" />
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
