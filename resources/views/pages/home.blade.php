@extends('layouts.site')

@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description', [
    'name' => setting('site_name'),
    'beds' => setting('bed_count'),
    'doctors' => setting('stat_doctors'),
]))

@section('content')

{{-- ======================= HERO ======================= --}}
<section class="relative overflow-hidden bg-navy-900">
    {{-- Decorative depth: a drifting grid and two slow orbs. All of it is
         switched off under prefers-reduced-motion by the global rule. --}}
    <div aria-hidden="true" class="hero-grid opacity-[0.12]"></div>
    <div aria-hidden="true" class="orb -right-40 -top-40 h-[32rem] w-[32rem] bg-teal-500/25"></div>
    <div aria-hidden="true" class="orb -bottom-52 -left-40 h-[28rem] w-[28rem] bg-navy-400/20" style="--anim-delay:-6s"></div>

    <div class="shell relative grid items-center gap-14 py-16 lg:grid-cols-12 lg:py-24">

        <div class="lg:col-span-7">
            <p class="eyebrow anim-fade-up text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ setting('accreditation') }}
            </p>

            <h1 class="h-display anim-fade-up mt-5 text-white" style="--anim-delay:90ms">
                {{ __('home.hero.heading_line_1') }}<br class="hidden sm:block">
                {{ __('home.hero.heading_line_2_before') }}
                <span class="text-teal-300">{{ __('home.hero.heading_line_2_accent') }}</span>
            </h1>

            <p class="lede anim-fade-up mt-6 max-w-xl text-white/70" style="--anim-delay:180ms">
                {{ __('home.hero.lede', [
                    'doctors' => setting('stat_doctors'),
                    'departments' => setting('stat_departments'),
                    'icu' => setting('stat_icu_beds'),
                    'city' => str(setting('address_city'))->before(','),
                ]) }}
            </p>

            <div class="anim-fade-up mt-9 flex flex-wrap items-center gap-3" style="--anim-delay:270ms">
                <a href="{{ route('appointment.create') }}" class="btn-accent btn-lg btn-nudge">
                    <x-icon name="calendar-check" size="18" />
                    {{ __('common.book_appointment') }}
                    <x-icon name="arrow-right" size="18" />
                </a>
                <a href="{{ route('doctors.index') }}"
                   class="btn btn-lg border border-white/25 text-white transition hover:border-white/50 hover:bg-white/10">
                    <x-icon name="search" size="18" />
                    {{ __('common.find_a_doctor') }}
                </a>
            </div>

            <dl class="anim-fade-up mt-12 grid max-w-lg grid-cols-2 gap-x-8 gap-y-6 border-t border-white/10 pt-8 sm:grid-cols-4"
                style="--anim-delay:360ms">
                @foreach ([
                    ['stat_doctors', __('home.hero.stat_consultants'), '+'],
                    ['stat_beds', __('home.hero.stat_beds'), ''],
                    ['stat_departments', __('home.hero.stat_departments'), ''],
                    ['stat_years', __('home.hero.stat_years'), ''],
                ] as [$key, $label, $suffix])
                    <div>
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="font-display text-3xl font-extrabold text-white" data-countup>
                            {{ setting($key) }}{{ $suffix }}
                        </dd>
                        <p class="mt-1 text-xs font-medium tracking-wide text-white/50">{{ $label }}</p>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Quick appointment launcher --}}
        <div class="lg:col-span-5">
            <div class="card anim-scale-in overflow-hidden p-0 shadow-lift" style="--anim-delay:220ms">
                <div class="border-b border-mist-200 bg-mist-50 px-7 py-5">
                    <h2 class="font-display text-lg font-bold text-navy-900">{{ __('home.booker.heading') }}</h2>
                    <p class="mt-1 text-sm text-navy-900/55">{{ __('home.booker.lede') }}</p>
                </div>

                <form action="{{ route('appointment.create') }}" method="GET" class="space-y-4 p-7">
                    <div>
                        <label for="hero-department" class="label">{{ __('home.booker.department') }}</label>
                        <select id="hero-department" name="department" class="input">
                            <option value="">{{ __('common.any_department') }}</option>
                            @foreach ($departmentOptions as $dept)
                                <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        {{ __('home.booker.continue') }}
                        <x-icon name="arrow-right" size="16" />
                    </button>

                    <p class="text-center text-xs text-navy-900/45">
                        {{ __('home.booker.prefer_to_talk') }}
                        <a href="tel:{{ setting('appointment_number') }}" class="font-semibold text-teal-700 hover:underline">
                            {{ setting('appointment_number') }}
                        </a>
                    </p>
                </form>

                <div class="flex items-center gap-3 border-t border-mist-200 bg-urgent-50 px-7 py-4">
                    <x-icon name="ambulance" size="22" class="shrink-0 text-urgent-600" />
                    <p class="text-sm text-navy-900/70">
                        <span class="font-semibold text-urgent-700">{{ __('home.booker.emergency_label') }}</span>
                        {{ __('home.booker.emergency_body') }}
                        <a href="tel:{{ setting('hotline') }}" class="font-bold text-urgent-700 hover:underline">{{ setting('hotline') }}</a>.
                        {{ __('home.booker.emergency_suffix') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================= QUICK ACTIONS ======================= --}}
<section class="relative z-10 -mt-8 lg:-mt-10">
    <div class="shell">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6"
             data-reveal-stagger="60">
            @foreach ([
                ['emergency', 'ambulance', __('home.quick.emergency'), __('home.quick.emergency_sub'), 'urgent'],
                ['doctors.index', 'user-round', __('home.quick.doctors'), __('home.quick.doctors_sub', ['count' => setting('stat_doctors')]), 'teal'],
                ['appointment.create', 'calendar-check', __('home.quick.appointment'), __('home.quick.appointment_sub'), 'teal'],
                ['departments.index', 'building', __('home.quick.departments'), __('home.quick.departments_sub', ['count' => setting('stat_departments')]), 'navy'],
                ['diagnostics.index', 'microscope', __('home.quick.diagnostics'), __('home.quick.diagnostics_sub'), 'navy'],
                ['packages.index', 'check-circle', __('home.quick.checks'), __('home.quick.checks_sub', ['price' => number_format($cheapestPackage)]), 'navy'],
            ] as [$route, $icon, $label, $sub, $tone])
                <a href="{{ route($route) }}"
                   class="card-interactive reveal group flex flex-col items-center gap-2 p-5 text-center">
                    <span @class([
                        'grid h-11 w-11 place-items-center rounded-xl transition duration-300 ease-out group-hover:scale-110',
                        'bg-urgent-50 text-urgent-600 group-hover:bg-urgent-600 group-hover:text-white' => $tone === 'urgent',
                        'bg-teal-50 text-teal-700 group-hover:bg-teal-600 group-hover:text-white' => $tone === 'teal',
                        'bg-navy-50 text-navy-700 group-hover:bg-navy-900 group-hover:text-white' => $tone === 'navy',
                    ])>
                        <x-icon :name="$icon" size="21" />
                    </span>
                    <span class="text-sm font-semibold text-navy-900 transition group-hover:text-teal-700">{{ $label }}</span>
                    <span class="text-[11px] text-navy-900/50">{{ $sub }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= CENTRES OF EXCELLENCE ======================= --}}
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.centres.eyebrow')"
            :title="__('home.centres.title')"
            :lede="__('home.centres.lede')"
            :link="route('departments.index')"
            :link-label="__('home.centres.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger="80">
            @foreach ($centres as $department)
                <x-department-card :department="$department" class="reveal" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= FIND A DOCTOR ======================= --}}
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.doctors.eyebrow')"
            :title="__('home.doctors.title')"
            :lede="__('home.doctors.lede')"
            :link="route('doctors.index')"
            :link-label="__('home.doctors.link')"
            class="reveal" />

        <form action="{{ route('doctors.index') }}" method="GET" class="reveal mt-10">
            <div class="card flex flex-col gap-3 p-3 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-navy-900/35" />
                    <label for="home-doctor-q" class="sr-only">{{ __('home.doctors.search_label') }}</label>
                    <input id="home-doctor-q" type="search" name="q"
                           placeholder="{{ __('home.doctors.search_placeholder') }}"
                           class="input border-0 pl-11 shadow-none focus:ring-0">
                </div>

                <div class="sm:w-64">
                    <label for="home-doctor-dept" class="sr-only">{{ __('home.booker.department') }}</label>
                    <select id="home-doctor-dept" name="department" class="input border-0 shadow-none focus:ring-0">
                        <option value="">{{ __('common.all_departments') }}</option>
                        @foreach ($departmentOptions as $dept)
                            <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary sm:px-8">{{ __('home.doctors.search_button') }}</button>
            </div>
        </form>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger="70">
            @foreach ($doctors as $doctor)
                <x-doctor-card :doctor="$doctor" class="reveal" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= SERVICES ======================= --}}
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.services.eyebrow')"
            :title="__('home.services.title')"
            :lede="__('home.services.lede')"
            :link="route('services.index')"
            :link-label="__('home.services.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="80">
            @foreach ($services as $service)
                <a href="{{ route('services.show', $service) }}"
                   class="card-interactive reveal group flex flex-col p-7">
                    <div class="flex items-center justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-navy-50 text-navy-800
                                     transition duration-300 ease-out group-hover:scale-105 group-hover:bg-navy-900 group-hover:text-white">
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
                    <span class="card-arrow mt-auto pt-5 text-sm font-semibold text-teal-700">
                        {{ __('common.learn_more') }} →
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= WHY CHOOSE US ======================= --}}
<section class="section bg-navy-900 text-white">
    <div class="shell grid gap-14 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <p class="eyebrow text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ __('home.why.eyebrow', ['name' => setting('site_name')]) }}
            </p>
            <h2 class="h-section mt-3 text-white">{{ __('home.why.title') }}</h2>
            <p class="lede mt-5 text-white/65">{{ __('home.why.lede') }}</p>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('about') }}" class="btn-accent">{{ __('home.why.about_cta') }}</a>
                <a href="{{ route('contact') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                    {{ __('home.why.visit_cta') }}
                </a>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="grid gap-4 sm:grid-cols-2" data-reveal-stagger="80">
                @foreach ([
                    ['ambulance', __('home.why.triage_title'), __('home.why.triage_body')],
                    ['activity', __('home.why.icu_title'), __('home.why.icu_body', ['count' => setting('stat_icu_beds')])],
                    ['shield-check', __('home.why.accredited_title'), __('home.why.accredited_body')],
                    ['users', __('home.why.consultants_title', ['count' => setting('stat_doctors')]), __('home.why.consultants_body', ['departments' => setting('stat_departments')])],
                    ['heart-pulse', __('home.why.cardiac_title'), __('home.why.cardiac_body')],
                    ['file-text', __('home.why.reports_title'), __('home.why.reports_body')],
                ] as [$icon, $title, $body])
                    <div class="reveal group rounded-2xl border border-white/10 bg-white/5 p-6 transition duration-300 ease-out
                                hover:-translate-y-1 hover:border-teal-400/30 hover:bg-white/[0.08]">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300
                                     transition duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-500/25">
                            <x-icon :name="$icon" size="20" />
                        </span>
                        <h3 class="mt-4 font-display text-base font-bold text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/60">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ======================= HEALTH PACKAGES ======================= --}}
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.packages.eyebrow')"
            :title="__('home.packages.title')"
            :lede="__('home.packages.lede')"
            :link="route('packages.index')"
            :link-label="__('home.packages.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 lg:grid-cols-3" data-reveal-stagger="90">
            @foreach ($packages as $package)
                <x-package-card :package="$package" class="reveal" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= TESTIMONIALS ======================= --}}
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.testimonials.eyebrow')"
            :title="__('home.testimonials.title')"
            :lede="__('home.testimonials.lede', ['name' => setting('site_name')])"
            align="center"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="80">
            @foreach ($testimonials->take(6) as $testimonial)
                <figure class="card-hover reveal group flex h-full flex-col p-7">
                    <x-icon name="quote" size="26" stroke="1.4"
                            class="text-teal-200 transition duration-300 ease-out group-hover:scale-110 group-hover:text-teal-300" />

                    <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-navy-900/75">
                        “{{ $testimonial->quote }}”
                    </blockquote>

                    <figcaption class="mt-6 flex items-center gap-3 border-t border-mist-200 pt-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-navy-900 text-xs font-bold text-white">
                            {{ str($testimonial->patient_name)->substr(0, 1) }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-navy-900">{{ $testimonial->patient_name }}</span>
                            <span class="block truncate text-xs text-navy-900/50">
                                {{ $testimonial->treatment }} · {{ $testimonial->location }}
                            </span>
                        </span>
                        <x-rating :rating="$testimonial->rating" class="ml-auto shrink-0" />
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= HEALTH HUB ======================= --}}
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.posts.eyebrow')"
            :title="__('home.posts.title')"
            :lede="__('home.posts.lede')"
            :link="route('posts.index')"
            :link-label="__('home.posts.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-3" data-reveal-stagger="90">
            @foreach ($posts as $post)
                <x-post-card :post="$post" class="reveal" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= VISIT / CONTACT ======================= --}}
<section class="pb-20">
    <div class="shell">
        <div class="reveal overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-navy-900 to-navy-950 text-white">
            <div class="grid gap-10 p-9 sm:p-12 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <h2 class="h-section text-white">{{ __('home.visit.title') }}</h2>
                    <p class="lede mt-4 text-white/65">
                        {{ __('home.visit.lede', [
                            'address' => setting('address_line'),
                            'city' => setting('address_city'),
                        ]) }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('contact') }}" class="btn-accent">
                            <x-icon name="map-pin" size="16" /> {{ __('home.visit.directions_cta') }}
                        </a>
                        <a href="tel:{{ setting('hotline') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="grid gap-3" data-reveal-stagger="80">
                        @foreach ([
                            ['ambulance', __('home.visit.ambulance'), setting('ambulance_number'), 'tel:' . setting('ambulance_number')],
                            ['calendar', __('home.visit.appointments'), setting('appointment_number'), 'tel:' . setting('appointment_number')],
                            ['globe', __('home.visit.international'), setting('international_desk'), 'tel:' . setting('international_desk')],
                        ] as [$icon, $label, $value, $href])
                            <a href="{{ $href }}"
                               class="reveal group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4
                                      transition duration-300 ease-out hover:translate-x-1 hover:border-teal-400/30 hover:bg-white/10">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300
                                             transition duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-500/25">
                                    <x-icon :name="$icon" size="19" />
                                </span>
                                <span>
                                    <span class="block text-xs text-white/50">{{ $label }}</span>
                                    <span class="block font-display text-base font-bold text-white">{{ $value }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
