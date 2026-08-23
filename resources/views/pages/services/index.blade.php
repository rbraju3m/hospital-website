@extends('layouts.site')

@section('title', __('services.index.meta_title'))
@section('meta_description', __('services.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('services.index.eyebrow')"
    :title="__('services.index.title')"
    :lede="__('services.index.lede')"
    :crumbs="[__('services.index.crumb') => null]" />

@php
    // Key order also sets the display order of the sections below.
    $groupKeys = ['clinical', 'diagnostic', 'support', 'patient-care'];
@endphp

@foreach ($groupKeys as $key)
    @continue(! isset($grouped[$key]))
    <section @class(['section', 'bg-mist-50' => $loop->odd])>
        <div class="shell">
            <x-section-heading :eyebrow="__('services.groups.'.$key)"
                               :title="__('services.groups.'.$key.'_blurb')" class="reveal" />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($grouped[$key] as $service)
                    <a href="{{ route('services.show', $service) }}"
                       class="card-interactive group flex flex-col p-7 reveal"
                       style="transition-delay: {{ $loop->index * 60 }}ms">
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-50 text-teal-700
                                         transition duration-300 group-hover:bg-teal-600 group-hover:text-white">
                                <x-icon :name="$service->icon" size="24" />
                            </span>
                            @if ($service->is_247)
                                <span class="chip-accent">{{ __('services.badge_247') }}</span>
                            @endif
                        </div>

                        <h3 class="mt-5 font-display text-lg font-bold text-navy-900 group-hover:text-teal-700">
                            {{ $service->name }}
                        </h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $service->summary }}</p>
                        <span class="mt-auto pt-5 text-sm font-semibold text-teal-700 transition group-hover:translate-x-0.5">
                            {{ __('common.learn_more') }} →
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endforeach

@endsection
