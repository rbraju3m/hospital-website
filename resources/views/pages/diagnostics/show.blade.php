@extends('layouts.site')

@section('title', $test->name)
@section('meta_description', $test->summary ?: (string) setting('site_tagline'))

@section('content')

<x-page-hero
    :eyebrow="category_label('diagnostics', $test->category)"
    :title="$test->name"
    :lede="$test->summary"
    :crumbs="[__('diagnostics.show.crumb') => route('diagnostics.index'), $test->name => null]">

    <div class="flex flex-wrap items-end gap-6">
        <div>
            <p class="text-xs uppercase tracking-[0.18em] text-white/50">{{ __('diagnostics.show.price_label') }}</p>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="font-display text-3xl font-extrabold text-white">৳{{ number_format($test->effectivePrice()) }}</span>
                @if ($test->savingsPercent())
                    <span class="text-base text-white/45 line-through">৳{{ number_format($test->price) }}</span>
                @endif
            </p>
            <p class="mt-1 text-xs text-white/50">{{ __('diagnostics.show.price_hint') }}</p>
        </div>

        <a href="#request" class="btn-accent">
            <x-icon name="calendar-check" size="16" /> {{ __('diagnostics.request.title') }}
        </a>
        <a href="tel:{{ setting('hotline') }}" class="btn border border-white/25 text-white hover:bg-white/10">
            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
        </a>
    </div>
</x-page-hero>

{{-- The three facts a patient actually needs before turning up. --}}
<section class="border-b border-mist-200 bg-mist-50">
    <div class="shell grid gap-5 py-10 sm:grid-cols-3">
        @php
            $facts = array_filter([
                [__('diagnostics.show.sample_title'), $test->sample_type, 'syringe'],
                [__('diagnostics.show.report_title'), $test->report_time, 'clock'],
                [__('diagnostics.show.code_label'), $test->code, 'file-text'],
            ], fn ($fact) => filled($fact[1]));
        @endphp

        @foreach ($facts as [$label, $value, $icon])
            <div class="flex items-start gap-3">
                <x-icon :name="$icon" size="20" class="mt-0.5 shrink-0 text-teal-600" />
                <div>
                    <p class="text-xs text-navy-900/50">{{ $label }}</p>
                    <p class="font-medium text-navy-900">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">{{ __('diagnostics.show.preparation_title') }}</h2>
            <p class="mt-6 text-base leading-relaxed text-navy-900/70">
                {{ $test->preparation ?: __('diagnostics.show.no_preparation') }}
            </p>

            @if ($test->is_home_collection)
                <div class="card mt-8 flex items-start gap-4 bg-teal-50/60 p-6">
                    <x-icon name="droplet" size="22" class="mt-0.5 shrink-0 text-teal-700" />
                    <div>
                        <h3 class="font-display text-base font-bold text-navy-900">
                            {{ __('diagnostics.show.home_collection_title') }}
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-navy-900/70">
                            {{ __('diagnostics.show.home_collection_body', ['phone' => setting('hotline')]) }}
                        </p>
                    </div>
                </div>
            @endif

            <h3 class="mt-14 font-display text-xl font-bold text-navy-900">{{ __('diagnostics.show.how_title') }}</h3>
            <ol class="mt-6 space-y-4">
                @foreach (['how_1', 'how_2', 'how_3'] as $index => $step)
                    <li class="flex items-start gap-4">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-navy-900 text-xs font-bold text-white">
                            {{ $index + 1 }}
                        </span>
                        <p class="text-sm leading-relaxed text-navy-900/70">{{ __("diagnostics.show.{$step}") }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        <aside class="lg:col-span-5">
            @include('pages.diagnostics.partials.request-form', ['test' => $test])
        </aside>
    </div>
</section>

@if ($related->isNotEmpty())
    <section class="section bg-mist-50 pt-0">
        <div class="shell">
            <h2 class="font-display text-xl font-bold text-navy-900">
                {{ __('diagnostics.show.related_title', ['category' => category_label('diagnostics', $test->category)]) }}
            </h2>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $other)
                    <a href="{{ route('diagnostics.show', $other) }}"
                       class="card-interactive flex items-center justify-between gap-4 p-5">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-navy-900">{{ $other->name }}</span>
                            @if ($other->report_time)
                                <span class="mt-0.5 block truncate text-xs text-navy-900/50">{{ $other->report_time }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 font-display font-bold text-navy-900">
                            ৳{{ number_format($other->effectivePrice()) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
