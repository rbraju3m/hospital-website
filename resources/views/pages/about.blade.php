@extends('layouts.site')

@section('title', __('pages.about.meta_title', ['name' => setting('site_name')]))
@section('meta_description', __('pages.about.meta_description', [
    'name' => setting('site_name'),
    'beds' => setting('bed_count'),
    'year' => setting('established_year'),
]))

@section('content')

<x-page-hero
    :eyebrow="__('pages.about.eyebrow')"
    :title="__('pages.about.title')"
    :lede="__('pages.about.lede', [
        'year' => setting('established_year'),
        'name' => setting('site_name'),
        'beds' => setting('bed_count'),
    ])"
    :crumbs="[__('pages.about.crumb') => null]" />

{{-- Stats --}}
<section class="border-b border-mist-200 bg-mist-50">
    <div class="shell grid grid-cols-2 gap-8 py-12 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['stat_years', __('pages.about.stat_years')],
            ['stat_beds', __('pages.about.stat_beds')],
            ['stat_icu_beds', __('pages.about.stat_icu')],
            ['stat_doctors', __('pages.about.stat_consultants')],
            ['stat_departments', __('pages.about.stat_departments')],
            ['stat_patients_yearly', __('pages.about.stat_patients')],
        ] as [$key, $label])
            <div>
                <p class="font-display text-3xl font-extrabold text-navy-900">{{ setting($key) }}</p>
                <p class="mt-1 text-xs font-medium tracking-wide text-navy-900/50">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="shell grid gap-14 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">{{ __('pages.about.story_title') }}</h2>
            <div class="mt-6 space-y-4 text-base leading-relaxed text-navy-900/70">
                <p>{{ __('pages.about.story_1', ['name' => setting('site_name'), 'year' => setting('established_year')]) }}</p>
                <p>{{ __('pages.about.story_2') }}</p>
                <p>{{ __('pages.about.story_3', [
                    'beds' => setting('bed_count'),
                    'icu' => setting('stat_icu_beds'),
                    'patients' => setting('stat_patients_yearly'),
                ]) }}</p>
                <p>{{ __('pages.about.story_4') }}</p>
            </div>

            {{-- Two photographs under the story: the place the copy is about,
                 which a page of prose about a building otherwise never shows. --}}
            @php
                $storyImages = array_filter([demo_image('about'), demo_image('cover', 12, 'about')]);
            @endphp

            @if ($storyImages)
                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    @foreach ($storyImages as $index => $image)
                        <figure class="media-frame reveal reveal-clip {{ $index === 0 ? 'aspect-[4/5]' : 'aspect-[4/5] sm:mt-8' }}"
                                style="--reveal-delay: {{ $index * 120 }}ms">
                            <img src="{{ $image }}" alt="" loading="lazy" data-fade>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:col-span-5">
            <div class="card p-8">
                <h3 class="font-display text-lg font-bold text-navy-900">{{ __('pages.about.standards_title') }}</h3>
                <ul class="mt-6 space-y-6">
                    @foreach ([
                        ['ambulance', __('pages.about.standard_1_title'), __('pages.about.standard_1_body')],
                        ['users', __('pages.about.standard_2_title'), __('pages.about.standard_2_body')],
                        ['file-text', __('pages.about.standard_3_title'), __('pages.about.standard_3_body')],
                        ['shield-check', __('pages.about.standard_4_title'), __('pages.about.standard_4_body')],
                    ] as [$icon, $title, $body])
                        <li class="flex gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700">
                                <x-icon :name="$icon" size="20" />
                            </span>
                            <div>
                                <p class="font-semibold text-navy-900">{{ $title }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-navy-900/60">{{ $body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section bg-navy-900 text-white">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('pages.about.values_eyebrow')"
            :title="__('pages.about.values_title')"
            align="center"
            class="reveal [&_h2]:text-white [&_p.lede]:text-white/65" />

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['heart-pulse', __('pages.about.value_1_title'), __('pages.about.value_1_body')],
                ['award', __('pages.about.value_2_title'), __('pages.about.value_2_body')],
                ['globe', __('pages.about.value_3_title'), __('pages.about.value_3_body')],
            ] as [$icon, $title, $body])
                <div class="reveal rounded-2xl border border-white/10 bg-white/5 p-8"
                     style="transition-delay: {{ $loop->index * 80 }}ms">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-500/15 text-teal-300">
                        <x-icon :name="$icon" size="24" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-bold text-white">{{ $title }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/65">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="card flex flex-col items-center gap-6 p-10 text-center sm:p-14">
            <h2 class="font-display text-2xl font-bold text-navy-900">
                {{ __('pages.about.cta_title', ['doctors' => $doctorCount, 'departments' => $departmentCount]) }}
            </h2>
            <p class="max-w-xl text-navy-900/60">{{ __('pages.about.cta_body') }}</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('doctors.index') }}" class="btn-accent">{{ __('common.find_a_doctor') }}</a>
                <a href="{{ route('departments.index') }}" class="btn-outline">{{ __('pages.about.cta_browse') }}</a>
            </div>
        </div>
    </div>
</section>

@endsection
