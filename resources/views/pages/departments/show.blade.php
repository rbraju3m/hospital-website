@extends('layouts.site')

@section('title', $department->name)
@section('meta_description', $department->summary)

@section('content')

<x-page-hero
    :eyebrow="$department->is_centre_of_excellence ? 'Centre of Excellence' : 'Clinical Department'"
    :title="$department->name"
    :lede="$department->summary"
    :crumbs="['Departments' => route('departments.index'), $department->name => null]">

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('appointment.create', ['department' => $department->slug]) }}" class="btn-accent">
            <x-icon name="calendar-check" size="16" /> Book an appointment
        </a>
        @if ($department->phone)
            <a href="tel:{{ $department->phone }}" class="btn border border-white/25 text-white hover:bg-white/10">
                <x-icon name="phone" size="16" /> {{ $department->phone }}
            </a>
        @endif
        @if ($department->location)
            <span class="flex items-center gap-2 text-sm text-white/60">
                <x-icon name="map-pin" size="16" /> {{ $department->location }}
            </span>
        @endif
    </div>
</x-page-hero>

{{-- Highlights strip --}}
@if ($department->highlights)
    <section class="border-b border-mist-200 bg-mist-50">
        <div class="shell grid gap-5 py-10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($department->highlights as $highlight)
                <div class="flex items-start gap-3">
                    <x-icon name="check-circle" size="20" class="mt-0.5 shrink-0 text-teal-600" />
                    <p class="text-sm font-medium text-navy-900/80">{{ $highlight }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">

        <div class="lg:col-span-8">
            <h2 class="h-section">About the department</h2>
            <div class="mt-6 space-y-4 text-base leading-relaxed text-navy-900/70">
                @foreach (preg_split('/\n+/', $department->description) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if ($department->treatments)
                <h3 class="mt-14 font-display text-xl font-bold text-navy-900">Treatments & procedures</h3>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($department->treatments as $treatment)
                        <li class="flex items-start gap-3 rounded-xl border border-mist-200 bg-white p-4">
                            <x-icon name="check" size="16" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                            <span class="text-sm text-navy-900/75">{{ $treatment }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <h3 class="font-display text-lg font-bold text-navy-900">Department contact</h3>
                    <dl class="mt-5 space-y-4 text-sm">
                        @if ($department->location)
                            <div class="flex gap-3">
                                <x-icon name="map-pin" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                                <div>
                                    <dt class="text-xs text-navy-900/50">Location</dt>
                                    <dd class="font-medium text-navy-900">{{ $department->location }}</dd>
                                </div>
                            </div>
                        @endif
                        @if ($department->phone)
                            <div class="flex gap-3">
                                <x-icon name="phone" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                                <div>
                                    <dt class="text-xs text-navy-900/50">Direct line</dt>
                                    <dd><a href="tel:{{ $department->phone }}" class="font-medium text-navy-900 hover:text-teal-700">{{ $department->phone }}</a></dd>
                                </div>
                            </div>
                        @endif
                        <div class="flex gap-3">
                            <x-icon name="users" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Consultants</dt>
                                <dd class="font-medium text-navy-900">{{ $department->doctors->count() }} specialists</dd>
                            </div>
                        </div>
                    </dl>

                    <a href="{{ route('appointment.create', ['department' => $department->slug]) }}"
                       class="btn-accent mt-6 w-full">Book an appointment</a>
                </div>

                <div class="card bg-urgent-50 p-7">
                    <div class="flex items-center gap-3">
                        <x-icon name="ambulance" size="22" class="text-urgent-600" />
                        <h3 class="font-display text-base font-bold text-navy-900">Emergency</h3>
                    </div>
                    <p class="mt-3 text-sm text-navy-900/65">
                        For urgent symptoms, go straight to the Emergency Department — no appointment or referral needed.
                    </p>
                    <a href="tel:{{ setting('hotline') }}" class="btn-urgent mt-5 w-full">
                        <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</section>

@if ($department->doctors->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading
                eyebrow="Our Team"
                :title="'Consultants in ' . $department->name"
                lede="Every profile lists chamber times, consultation fees and online availability."
                class="reveal" />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($department->doctors as $doctor)
                    <x-doctor-card :doctor="$doctor" class="reveal"
                                   style="transition-delay: {{ $loop->index * 50 }}ms" />
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="section">
    <div class="shell">
        <x-section-heading eyebrow="Explore" title="Other departments" class="reveal" />
        <div class="mt-10 flex flex-wrap gap-2.5">
            @foreach ($related as $other)
                <a href="{{ route('departments.show', $other) }}"
                   class="chip transition hover:bg-navy-900 hover:text-white">
                    <x-icon :name="$other->icon" size="14" />
                    {{ $other->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>

@endsection
