@extends('layouts.site')

@section('title', 'Departments & Centres of Excellence')
@section('meta_description', 'Explore all ' . ($centres->count() + $departments->count()) . ' clinical departments at RBR Hospital, from cardiac sciences and neurosciences to paediatrics and emergency medicine.')

@section('content')

<x-page-hero
    eyebrow="Clinical Departments"
    title="Specialist care across {{ $centres->count() + $departments->count() }} departments"
    lede="Eight centres of excellence supported by a full range of clinical specialties — organised so that a patient with more than one problem is treated by one coordinated team."
    :crumbs="['Departments' => null]" />

<section class="section">
    <div class="shell">
        <x-section-heading
            eyebrow="Centres of Excellence"
            title="Where we go deepest"
            lede="These centres combine physicians, surgeons, imaging and rehabilitation into a single pathway, with multidisciplinary review for complex cases."
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
            eyebrow="All Specialties"
            title="Other clinical departments"
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
                <h2 class="font-display text-2xl font-bold text-navy-900">Not sure which department you need?</h2>
                <p class="mt-3 text-navy-900/60">
                    Start with Internal Medicine. Our physicians assess undifferentiated symptoms and refer you
                    to the right specialist — which is faster than guessing and being sent back.
                </p>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('appointment.create') }}" class="btn-accent">Book with a physician</a>
                <a href="tel:{{ setting('appointment_number') }}" class="btn-outline">
                    <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
