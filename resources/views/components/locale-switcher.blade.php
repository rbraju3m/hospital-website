@props(['variant' => 'bar'])

@php
    $locales = config('app.available_locales', []);
    $current = app()->getLocale();
@endphp

{{-- Nothing to switch between: render nothing rather than a dead control. --}}
@if (count($locales) > 1)
    @if ($variant === 'drawer')
        <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-xl border border-mist-200 p-1.5']) }}>
            <span class="sr-only">{{ __('common.switch_language') }}</span>
            @foreach ($locales as $code => $meta)
                <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}"
                   @class([
                       'flex-1 rounded-lg px-3 py-2 text-center text-sm font-semibold transition',
                       'bg-navy-900 text-white' => $code === $current,
                       'text-navy-900/70 hover:bg-mist-50' => $code !== $current,
                   ])
                   @if ($code === $current) aria-current="true" @endif>
                    {{ $meta['native'] }}
                </a>
            @endforeach
        </div>
    @else
        <div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
            <span class="sr-only">{{ __('common.switch_language') }}</span>
            @foreach ($locales as $code => $meta)
                <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}"
                   title="{{ $meta['native'] }}"
                   @class([
                       'rounded-md px-2 py-0.5 font-semibold transition',
                       'bg-white/15 text-white' => $code === $current,
                       'text-white/60 hover:text-white' => $code !== $current,
                   ])
                   @if ($code === $current) aria-current="true" @endif>
                    {{ $meta['short'] }}
                </a>
            @endforeach
        </div>
    @endif
@endif
