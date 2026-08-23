@extends('layouts.site')

@section('title', 'Find a Doctor')
@section('meta_description', 'Search ' . setting('stat_doctors') . '+ specialist consultants at RBR Hospital by name, specialty or department. View chamber times, fees and book online.')

@section('content')

<x-page-hero
    eyebrow="Our Consultants"
    title="Find a doctor"
    lede="Search by name, specialty or department. Each profile shows qualifications, chamber schedule, consultation fee and the next available appointment."
    :crumbs="['Find a Doctor' => null]" />

<section class="section">
    <div class="shell grid gap-10 lg:grid-cols-12">

        {{-- Filters --}}
        <aside class="lg:col-span-3">
            <form action="{{ route('doctors.index') }}" method="GET"
                  class="sticky top-24 space-y-6 rounded-[1.25rem] border border-mist-200 bg-white p-6 shadow-soft">

                <div>
                    <label for="filter-q" class="label">Search</label>
                    <div class="relative">
                        <x-icon name="search" size="17" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-navy-900/35" />
                        <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="Name or specialty" class="input pl-10">
                    </div>
                </div>

                <div>
                    <label for="filter-department" class="label">Department</label>
                    <select id="filter-department" name="department" class="input">
                        <option value="">All departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->slug }}" @selected(($filters['department'] ?? '') === $dept->slug)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <fieldset>
                    <legend class="label">Consultant gender</legend>
                    <div class="space-y-2">
                        @foreach (['' => 'Any', 'female' => 'Female', 'male' => 'Male'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-900/75">
                                <input type="radio" name="gender" value="{{ $value }}"
                                       @checked(($filters['gender'] ?? '') === $value)
                                       class="h-4 w-4 border-mist-200 text-teal-600 focus:ring-teal-500/30">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="filter-sort" class="label">Sort by</label>
                    <select id="filter-sort" name="sort" class="input">
                        @foreach (['name' => 'Name (A–Z)', 'experience' => 'Most experienced', 'fee' => 'Lowest fee'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">Apply</button>
                    @if (array_filter($filters))
                        <a href="{{ route('doctors.index') }}" class="btn-outline">Clear</a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <div class="lg:col-span-9">
            <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-mist-200 pb-5">
                <p class="text-sm text-navy-900/60">
                    <span class="font-display text-lg font-bold text-navy-900">{{ $doctors->total() }}</span>
                    {{ Str::plural('consultant', $doctors->total()) }} found
                    @if ($filters['q'] ?? null)
                        for “<span class="font-medium text-navy-900">{{ $filters['q'] }}</span>”
                    @endif
                </p>
                <p class="text-sm text-navy-900/45">
                    Page {{ $doctors->currentPage() }} of {{ max($doctors->lastPage(), 1) }}
                </p>
            </div>

            @if ($doctors->isEmpty())
                <div class="card mt-8 flex flex-col items-center gap-5 p-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-mist-100 text-navy-900/40">
                        <x-icon name="search" size="28" />
                    </span>
                    <div>
                        <h2 class="font-display text-xl font-bold text-navy-900">No consultants match that search</h2>
                        <p class="mt-2 text-navy-900/60">Try a broader term, or browse by department instead.</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a href="{{ route('doctors.index') }}" class="btn-outline">Clear filters</a>
                        <a href="{{ route('departments.index') }}" class="btn-accent">Browse departments</a>
                    </div>
                </div>
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($doctors as $doctor)
                        <x-doctor-card :doctor="$doctor" />
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $doctors->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

@endsection
