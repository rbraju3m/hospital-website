@extends('layouts.site')

@section('title', $service->name)
{{-- Never pass null to @section: Blade reads a null second argument as
     "capture until @endsection" and swallows the rest of the page. --}}
@section('meta_description', $service->summary ?: (string) setting('site_tagline'))

@section('content')

<x-page-hero
    :eyebrow="$service->is_247 ? __('services.show.eyebrow_247') : __('services.show.eyebrow')"
    :title="$service->name"
    :lede="$service->summary"
    :crumbs="[__('services.index.crumb') => route('services.index'), $service->name => null]">

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('appointment.create') }}" class="btn-accent">
            <x-icon name="calendar-check" size="16" /> {{ __('common.book_appointment') }}
        </a>
        <a href="tel:{{ setting('hotline') }}" class="btn border border-white/25 text-white hover:bg-white/10">
            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
        </a>
    </div>
</x-page-hero>

<x-cover-image :path="$service->untranslated('image')" :alt="$service->name" :seed="$service->id" group="service" />

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-8">
            <x-article-body :body="$service->description" />

            @if ($service->highlights)
                <h2 class="mt-14 font-display text-xl font-bold text-navy-900">{{ __('services.show.includes_title') }}</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($service->highlights as $highlight)
                        <div class="flex items-start gap-3 rounded-xl border border-mist-200 bg-white dark:bg-navy-100 p-5">
                            <x-icon name="check-circle" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <span class="text-sm text-navy-900/75">{{ $highlight }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">{{ __('services.show.contact_title') }}</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="phone" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('services.show.hotline') }}</dt>
                                <dd><a href="tel:{{ setting('hotline') }}" class="font-medium text-navy-900 hover:text-teal-700">{{ setting('hotline') }}</a></dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="clock" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('services.show.hours') }}</dt>
                                <dd class="font-medium text-navy-900">
                                    {{ $service->is_247 ? __('services.show.hours_247') : setting('opening_hours') }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="map-pin" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('services.show.address') }}</dt>
                                <dd class="font-medium text-navy-900">{{ setting('address_line') }}, {{ setting('address_city') }}</dd>
                            </div>
                        </div>
                    </dl>
                    <a href="{{ route('appointment.create') }}" class="btn-accent mt-6 w-full">{{ __('common.book_appointment') }}</a>
                </div>
            </div>
        </aside>
    </div>
</section>

@if ($related->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading :eyebrow="__('services.show.related_eyebrow')"
                               :title="__('services.show.related_title')"
                               :link="route('services.index')"
                               :link-label="__('services.show.related_link')" class="reveal" />
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($related as $other)
                    <a href="{{ route('services.show', $other) }}" class="card-interactive group flex flex-col p-6 reveal">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-teal-50 text-teal-700
                                     transition group-hover:bg-teal-600 group-hover:text-white">
                            <x-icon :name="$other->icon" size="22" />
                        </span>
                        <h3 class="mt-4 font-display text-base font-bold text-navy-900 group-hover:text-teal-700">{{ $other->name }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
