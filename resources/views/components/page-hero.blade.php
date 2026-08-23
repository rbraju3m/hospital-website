@props(['eyebrow' => null, 'title', 'lede' => null, 'crumbs' => []])

<section class="relative overflow-hidden bg-navy-900 text-white">
    {{-- Decorative grid + glow; purely presentational --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.15]"
         style="background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);background-size:64px 64px"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-teal-500/20 blur-3xl"></div>

    <div class="shell relative py-14 sm:py-20">
        @if ($crumbs)
            <nav aria-label="{{ __('common.breadcrumb') }}" class="mb-6">
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
            <p class="eyebrow text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="h-display mt-3 max-w-3xl text-white">{{ $title }}</h1>

        @if ($lede)
            <p class="lede mt-5 max-w-2xl text-white/70">{{ $lede }}</p>
        @endif

        @if (trim($slot))
            <div class="mt-8">{{ $slot }}</div>
        @endif
    </div>
</section>
