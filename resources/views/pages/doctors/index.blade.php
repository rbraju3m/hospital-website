@extends('layouts.site')

@section('title', __('doctors.index.meta_title'))
@section('meta_description', __('doctors.index.meta_description', [
    'count' => setting('stat_doctors'),
    'name' => setting('site_name'),
]))

@section('content')

<x-page-hero
    :eyebrow="__('doctors.index.eyebrow')"
    :title="__('doctors.index.title')"
    :lede="__('doctors.index.lede')"
    :crumbs="[__('doctors.index.crumb') => null]" />

<section class="section">
    <div class="shell grid gap-10 lg:grid-cols-12">

        {{-- Filters --}}
        <aside class="lg:col-span-3">
            <form action="{{ route('doctors.index') }}" method="GET"
                  class="sticky top-24 space-y-6 rounded-[1.25rem] border border-mist-200 bg-white dark:bg-navy-100 p-6 shadow-soft">

                <div>
                    <label for="filter-q" class="label">{{ __('doctors.index.search') }}</label>
                    <div class="relative">
                        <x-icon name="search" size="17" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-navy-900/35" />
                        <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ __('doctors.index.search_placeholder') }}" class="input pl-10">
                    </div>
                </div>

                <div>
                    <label for="filter-department" class="label">{{ __('doctors.index.department') }}</label>
                    <select id="filter-department" name="department" class="input">
                        <option value="">{{ __('common.all_departments') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->slug }}" @selected(($filters['department'] ?? '') === $dept->slug)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <fieldset>
                    <legend class="label">{{ __('doctors.index.gender_legend') }}</legend>
                    <div class="space-y-2">
                        @foreach (['' => __('doctors.index.gender_any'), 'female' => __('doctors.index.gender_female'), 'male' => __('doctors.index.gender_male')] as $value => $label)
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
                    <label for="filter-sort" class="label">{{ __('doctors.index.sort') }}</label>
                    <select id="filter-sort" name="sort" class="input">
                        @foreach (['name' => __('doctors.index.sort_name'), 'experience' => __('doctors.index.sort_experience'), 'fee' => __('doctors.index.sort_fee')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">{{ __('doctors.index.apply') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('doctors.index') }}" class="btn-outline">{{ __('doctors.index.clear') }}</a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <div class="lg:col-span-9">
            <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-mist-200 pb-5">
                <p class="text-sm text-navy-900/60">
                    {{ trans_choice('doctors.index.found', $doctors->total(), ['count' => $doctors->total()]) }}
                    @if ($filters['q'] ?? null)
                        {{ __('doctors.index.found_for', ['term' => $filters['q']]) }}
                    @endif
                </p>
                <p class="text-sm text-navy-900/45">
                    {{ __('doctors.index.page_of', [
                        'current' => $doctors->currentPage(),
                        'last' => max($doctors->lastPage(), 1),
                    ]) }}
                </p>
            </div>

            @if ($doctors->isEmpty())
                <div class="card mt-8 flex flex-col items-center gap-5 p-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-mist-100 text-navy-900/40">
                        <x-icon name="search" size="28" />
                    </span>
                    <div>
                        <h2 class="font-display text-xl font-bold text-navy-900">{{ __('doctors.index.empty_title') }}</h2>
                        <p class="mt-2 text-navy-900/60">{{ __('doctors.index.empty_body') }}</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a href="{{ route('doctors.index') }}" class="btn-outline">{{ __('doctors.index.empty_clear') }}</a>
                        <a href="{{ route('departments.index') }}" class="btn-accent">{{ __('doctors.index.empty_browse') }}</a>
                    </div>
                </div>
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3" data-reveal-stagger="60">
                    @foreach ($doctors as $doctor)
                        <x-doctor-card :doctor="$doctor" class="reveal" />
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
