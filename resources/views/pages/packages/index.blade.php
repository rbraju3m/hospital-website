@extends('layouts.site')

@section('title', 'Health Check-up Packages')
@section('meta_description', 'Executive, cardiac, diabetes, women\'s and senior health check packages at RBR Hospital — completed in a single visit with a consultant review.')

@section('content')

<x-page-hero
    eyebrow="Health Packages"
    title="Preventive check-ups worth the morning they take"
    lede="Structured screening completed in one visit, in a lounge separate from the main outpatient area, ending with a consultant who explains what the results mean."
    :crumbs="['Health Packages' => null]" />

<section class="section">
    <div class="shell">

        {{-- Category filter --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('packages.index') }}"
               @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>All packages</a>
            @foreach ($categories as $cat)
                <a href="{{ route('packages.index', ['category' => $cat]) }}"
                   @class(['btn-sm', $category === $cat ? 'btn-primary' : 'btn-outline'])>
                    {{ str($cat)->headline() }}
                </a>
            @endforeach
        </div>

        @if ($packages->isEmpty())
            <div class="card mt-10 p-14 text-center">
                <p class="text-navy-900/60">No packages in this category yet.</p>
            </div>
        @else
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-package-card :package="$package" class="reveal"
                                    style="transition-delay: {{ $loop->index * 50 }}ms" />
                @endforeach
            </div>
        @endif

        <div class="reveal card mt-14 flex flex-col items-center gap-6 p-10 text-center sm:p-14">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-teal-50 text-teal-700">
                <x-icon name="users" size="28" />
            </span>
            <div class="max-w-xl">
                <h2 class="font-display text-2xl font-bold text-navy-900">Screening for your organisation?</h2>
                <p class="mt-3 text-navy-900/60">
                    We run corporate health screening on site or at the hospital, with consolidated
                    anonymised reporting for HR and individual reports for each employee.
                </p>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('contact') }}" class="btn-accent">Request a corporate quote</a>
                <a href="tel:{{ setting('appointment_number') }}" class="btn-outline">
                    <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
