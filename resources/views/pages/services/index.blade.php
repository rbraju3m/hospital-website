@extends('layouts.site')

@section('title', 'Medical Services & Facilities')
@section('meta_description', 'Emergency care, intensive care, diagnostic imaging, laboratory, pharmacy, physiotherapy and more — the full range of services at RBR Hospital.')

@section('content')

<x-page-hero
    eyebrow="Services & Facilities"
    title="Everything a patient needs, on one campus"
    lede="Clinical services, diagnostics and patient support built so that a single visit rarely turns into three separate trips."
    :crumbs="['Services' => null]" />

@php
    $groupLabels = [
        'clinical' => ['Clinical Services', 'Direct patient treatment, from resuscitation to rehabilitation.'],
        'diagnostic' => ['Diagnostics', 'Imaging and laboratory services, with reports available online.'],
        'support' => ['Support Services', 'The infrastructure that keeps treatment moving.'],
        'patient-care' => ['Patient Services', 'Practical help around the medicine itself.'],
    ];
@endphp

@foreach ($groupLabels as $key => [$label, $blurb])
    @continue(! isset($grouped[$key]))
    <section @class(['section', 'bg-mist-50' => $loop->odd])>
        <div class="shell">
            <x-section-heading :eyebrow="$label" :title="$blurb" class="reveal" />

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
                                <span class="chip-accent">24/7</span>
                            @endif
                        </div>

                        <h3 class="mt-5 font-display text-lg font-bold text-navy-900 group-hover:text-teal-700">
                            {{ $service->name }}
                        </h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $service->summary }}</p>
                        <span class="mt-auto pt-5 text-sm font-semibold text-teal-700 transition group-hover:translate-x-0.5">
                            Learn more →
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endforeach

@endsection
