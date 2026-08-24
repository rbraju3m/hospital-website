@props(['eyebrow' => null, 'title', 'lede' => null, 'crumbs' => []])

<section class="relative overflow-hidden bg-navy-900 text-white">
    {{-- Decorative grid + glow; purely presentational --}}
    <div aria-hidden="true" class="hero-grid opacity-[0.15]"></div>
    <div aria-hidden="true" class="orb -right-32 -top-32 h-96 w-96 bg-teal-500/20"></div>
    <div aria-hidden="true" class="orb -bottom-40 left-1/4 h-72 w-72 bg-navy-400/15" style="--anim-delay:-5s"></div>

    <div class="shell relative py-14 sm:py-20">
        @if ($crumbs)
            <nav aria-label="{{ __('common.breadcrumb') }}" class="anim-fade-in mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/55">
                    <li><a href="{{ route('home') }}" class="transition hover:text-white">{{ __('common.home') }}</a></li>
                    @foreach ($crumbs as $label => $url)
                        <li aria-hidden="true"><x-icon name="chevron-right" size="14" /></li>
                        <li>
                            @if ($url)
                                <a href="{{ $url }}" class="transition hover:text-white">{{ $label }}</a>
                            @else
                                <span class="text-white/90">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        @if ($eyebrow)
            <p class="eyebrow anim-fade-up text-teal-300" style="--anim-delay:60ms">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="h-display anim-fade-up mt-3 max-w-3xl text-white" style="--anim-delay:120ms">{{ $title }}</h1>

        @if ($lede)
            <p class="lede anim-fade-up mt-5 max-w-2xl text-white/70" style="--anim-delay:200ms">{{ $lede }}</p>
        @endif

        @if (trim($slot))
            <div class="anim-fade-up mt-8" style="--anim-delay:280ms">{{ $slot }}</div>
        @endif
    </div>
</section>
