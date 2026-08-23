@extends('layouts.site')

@section('title', __('departments.index.meta_title'))
@section('meta_description', __('departments.index.meta_description', [
    'count' => $centres->count() + $departments->count(),
    'name' => setting('site_name'),
]))

@section('content')

<x-page-hero
    :eyebrow="__('departments.index.eyebrow')"
    :title="__('departments.index.title', ['count' => $centres->count() + $departments->count()])"
    :lede="__('departments.index.lede')"
    :crumbs="[__('departments.index.crumb') => null]" />

<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('departments.index.centres_eyebrow')"
            :title="__('departments.index.centres_title')"
            :lede="__('departments.index.centres_lede')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($centres as $department)
                <x-department-card :department="$department" class="reveal"
                                   style="transition-delay: {{ $loop->index * 50 }}ms" />
            @endforeach
        </div>
    </div>
</section>

<section class="section bg-mist-50 pt-0 sm:pt-0 lg:pt-0">
    <div class="shell pt-16 sm:pt-20 lg:pt-24">
        <x-section-heading
            :eyebrow="__('departments.index.others_eyebrow')"
            :title="__('departments.index.others_title')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($departments as $department)
                <x-department-card :department="$department" class="reveal"
                                   style="transition-delay: {{ $loop->index * 50 }}ms" />
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="reveal card flex flex-col items-center gap-6 p-10 text-center sm:p-14">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-teal-50 text-teal-700">
                <x-icon name="user-round" size="28" />
            </span>
            <div class="max-w-xl">
                <h2 class="font-display text-2xl font-bold text-navy-900">{{ __('departments.index.unsure_title') }}</h2>
                <p class="mt-3 text-navy-900/60">{{ __('departments.index.unsure_body') }}</p>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('appointment.create') }}" class="btn-accent">{{ __('departments.index.unsure_cta') }}</a>
                <a href="tel:{{ setting('appointment_number') }}" class="btn-outline">
                    <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
