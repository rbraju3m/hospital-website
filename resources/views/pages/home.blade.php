@extends('layouts.site')

@section('title', 'Advanced Medicine, Delivered with Care')
@section('meta_description', 'RBR Hospital is a ' . setting('bed_count') . '-bed multidisciplinary hospital in Dhaka offering 24/7 emergency care, ' . setting('stat_doctors') . '+ specialist consultants and online appointment booking.')

@section('content')

{{-- ======================= HERO ======================= --}}
<section class="relative overflow-hidden bg-navy-900">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.12]"
         style="background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);background-size:72px 72px"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full bg-teal-500/25 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-52 -left-40 h-[28rem] w-[28rem] rounded-full bg-navy-400/20 blur-3xl"></div>

    <div class="shell relative grid items-center gap-14 py-16 lg:grid-cols-12 lg:py-24">

        <div class="lg:col-span-7">
            <p class="eyebrow text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ setting('accreditation') }}
            </p>

            <h1 class="h-display mt-5 text-white">
                World-class healthcare,<br class="hidden sm:block">
                delivered with <span class="text-teal-300">compassion</span>
            </h1>

            <p class="lede mt-6 max-w-xl text-white/70">
                {{ setting('stat_doctors') }} specialist consultants across {{ setting('stat_departments') }} departments,
                {{ setting('stat_icu_beds') }} critical care beds, and an emergency department that never closes —
                in the heart of {{ str(setting('address_city'))->before(',') }}.
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-3">
                <a href="{{ route('appointment.create') }}" class="btn-accent btn-lg">
                    <x-icon name="calendar-check" size="18" />
                    Book an Appointment
                </a>
                <a href="{{ route('doctors.index') }}" class="btn btn-lg border border-white/25 text-white hover:bg-white/10">
                    <x-icon name="search" size="18" />
                    Find a Doctor
                </a>
            </div>

            <dl class="mt-12 grid max-w-lg grid-cols-2 gap-x-8 gap-y-6 border-t border-white/10 pt-8 sm:grid-cols-4">
                @foreach ([
                    ['stat_doctors', 'Consultants', '+'],
                    ['stat_beds', 'Beds', ''],
                    ['stat_departments', 'Departments', ''],
                    ['stat_years', 'Years of care', ''],
                ] as [$key, $label, $suffix])
                    <div>
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="font-display text-3xl font-extrabold text-white">
                            {{ setting($key) }}{{ $suffix }}
                        </dd>
                        <p class="mt-1 text-xs font-medium tracking-wide text-white/50">{{ $label }}</p>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Quick appointment launcher --}}
        <div class="lg:col-span-5">
            <div class="card overflow-hidden p-0 shadow-lift">
                <div class="border-b border-mist-200 bg-mist-50 px-7 py-5">
                    <h2 class="font-display text-lg font-bold text-navy-900">Book in three steps</h2>
                    <p class="mt-1 text-sm text-navy-900/55">Pick a department, choose your consultant, confirm a time.</p>
                </div>

                <form action="{{ route('appointment.create') }}" method="GET" class="space-y-4 p-7">
                    <div>
                        <label for="hero-department" class="label">Department</label>
                        <select id="hero-department" name="department" class="input">
                            <option value="">Any department</option>
                            @foreach ($departmentOptions as $dept)
                                <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Continue to booking
                        <x-icon name="arrow-right" size="16" />
                    </button>

                    <p class="text-center text-xs text-navy-900/45">
                        Prefer to talk? Call
                        <a href="tel:{{ setting('appointment_number') }}" class="font-semibold text-teal-700 hover:underline">
                            {{ setting('appointment_number') }}
                        </a>
                    </p>
                </form>

                <div class="flex items-center gap-3 border-t border-mist-200 bg-urgent-50 px-7 py-4">
                    <x-icon name="ambulance" size="22" class="shrink-0 text-urgent-600" />
                    <p class="text-sm text-navy-900/70">
                        <span class="font-semibold text-urgent-700">Emergency?</span>
                        Walk in any time or call
                        <a href="tel:{{ setting('hotline') }}" class="font-bold text-urgent-700 hover:underline">{{ setting('hotline') }}</a>.
                        No appointment needed.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================= QUICK ACTIONS ======================= --}}
<section class="relative z-10 -mt-8 lg:-mt-10">
    <div class="shell">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                ['emergency', 'ambulance', 'Emergency', '24 hours', 'urgent'],
                ['doctors.index', 'user-round', 'Find a Doctor', '180+ consultants', 'teal'],
                ['appointment.create', 'calendar-check', 'Appointment', 'Book online', 'teal'],
                ['departments.index', 'building', 'Departments', '16 specialties', 'navy'],
                ['services.index', 'microscope', 'Diagnostics', 'Lab & imaging', 'navy'],
                ['packages.index', 'check-circle', 'Health Checks', 'From ৳2,900', 'navy'],
            ] as [$route, $icon, $label, $sub, $tone])
                <a href="{{ route($route) }}"
                   class="card-interactive group flex flex-col items-center gap-2 p-5 text-center">
                    <span @class([
                        'grid h-11 w-11 place-items-center rounded-xl transition duration-300',
                        'bg-urgent-50 text-urgent-600 group-hover:bg-urgent-600 group-hover:text-white' => $tone === 'urgent',
                        'bg-teal-50 text-teal-700 group-hover:bg-teal-600 group-hover:text-white' => $tone === 'teal',
                        'bg-navy-50 text-navy-700 group-hover:bg-navy-900 group-hover:text-white' => $tone === 'navy',
                    ])>
                        <x-icon :name="$icon" size="21" />
                    </span>
                    <span class="text-sm font-semibold text-navy-900">{{ $label }}</span>
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
            eyebrow="Centres of Excellence"
            title="Specialist care organised around the condition, not the corridor"
            lede="Each centre brings physicians, surgeons, imaging and rehabilitation into one pathway, so patients are not left to coordinate their own care between departments."
            :link="route('departments.index')"
            link-label="All departments"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($centres as $department)
                <x-department-card :department="$department" class="reveal"
                                   style="transition-delay: {{ $loop->index * 60 }}ms" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= FIND A DOCTOR ======================= --}}
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            eyebrow="Our Consultants"
            title="Find the right doctor"
            lede="Search by name, specialty or department. Every profile shows chamber times, consultation fees and the next available slot."
            :link="route('doctors.index')"
            link-label="All consultants"
            class="reveal" />

        <form action="{{ route('doctors.index') }}" method="GET" class="reveal mt-10">
            <div class="card flex flex-col gap-3 p-3 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-navy-900/35" />
                    <label for="home-doctor-q" class="sr-only">Search doctors</label>
                    <input id="home-doctor-q" type="search" name="q"
                           placeholder="Doctor name, specialty or condition…"
                           class="input border-0 pl-11 shadow-none focus:ring-0">
                </div>

                <div class="sm:w-64">
                    <label for="home-doctor-dept" class="sr-only">Department</label>
                    <select id="home-doctor-dept" name="department" class="input border-0 shadow-none focus:ring-0">
                        <option value="">All departments</option>
                        @foreach ($departmentOptions as $dept)
                            <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary sm:px-8">Search</button>
            </div>
        </form>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($doctors as $doctor)
                <x-doctor-card :doctor="$doctor" class="reveal"
                               style="transition-delay: {{ $loop->index * 50 }}ms" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= SERVICES ======================= --}}
<section class="section">
    <div class="shell">
        <x-section-heading
            eyebrow="Medical Services"
            title="Everything a patient needs, on one campus"
            lede="From emergency resuscitation to routine physiotherapy — services designed so that a single visit rarely turns into three."
            :link="route('services.index')"
            link-label="All services"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <a href="{{ route('services.show', $service) }}"
                   class="card-interactive group flex flex-col p-7 reveal"
                   style="transition-delay: {{ $loop->index * 60 }}ms">
                    <div class="flex items-center justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-navy-50 text-navy-800
                                     transition duration-300 group-hover:bg-navy-900 group-hover:text-white">
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

{{-- ======================= WHY CHOOSE US ======================= --}}
<section class="section bg-navy-900 text-white">
    <div class="shell grid gap-14 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <p class="eyebrow text-teal-300"><span class="h-px w-6 bg-teal-400"></span> Why RBR Hospital</p>
            <h2 class="h-section mt-3 text-white">Standards you can check, not just claims</h2>
            <p class="lede mt-5 text-white/65">
                We publish the things that actually determine outcomes — response times, staffing ratios,
                accreditation — because those are what separate hospitals, not marketing language.
            </p>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('about') }}" class="btn-accent">About the hospital</a>
                <a href="{{ route('contact') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                    Visit us
                </a>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['ambulance', 'Emergency triage in under 5 minutes', 'Emergency physicians are on site around the clock, not on call from home.'],
                    ['activity', 'Resident intensivists, 24 hours', 'Ventilated patients are nursed one-to-one across ' . setting('stat_icu_beds') . ' critical care beds.'],
                    ['shield-check', 'JCI accredited, ISO certified', 'Independently audited against international patient-safety standards.'],
                    ['users', setting('stat_doctors') . '+ specialist consultants', 'Sub-specialty depth across ' . setting('stat_departments') . ' departments, with multidisciplinary review for complex cases.'],
                    ['heart-pulse', 'Door-to-balloon under 60 minutes', 'Two catheterisation labs mean a heart attack never waits for a planned case.'],
                    ['file-text', 'Reports online, not in a queue', 'Lab and imaging results download from the patient portal as soon as they are verified.'],
                ] as [$icon, $title, $body])
                    <div class="reveal rounded-2xl border border-white/10 bg-white/5 p-6"
                         style="transition-delay: {{ $loop->index * 60 }}ms">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300">
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
            eyebrow="Health Packages"
            title="Preventive check-ups, finished in one visit"
            lede="Structured screening completed in a single half-day, in a lounge separate from the main outpatient area — and a report that explains what the numbers mean."
            :link="route('packages.index')"
            link-label="All packages"
            class="reveal" />

        <div class="mt-12 grid gap-5 lg:grid-cols-3">
            @foreach ($packages as $package)
                <x-package-card :package="$package" class="reveal"
                                style="transition-delay: {{ $loop->index * 70 }}ms" />
            @endforeach
        </div>
    </div>
</section>

{{-- ======================= TESTIMONIALS ======================= --}}
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            eyebrow="Patient Stories"
            title="In their own words"
            lede="Shared with permission by patients and families treated at RBR Hospital."
            align="center"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($testimonials->take(6) as $testimonial)
                <figure class="card reveal flex h-full flex-col p-7"
                        style="transition-delay: {{ $loop->index * 60 }}ms">
                    <x-icon name="quote" size="26" class="text-teal-200" stroke="1.4" />

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
            eyebrow="Health Hub"
            title="Guidance from our consultants"
            lede="Practical, locally relevant health writing — dengue season, diabetes control, when chest pain needs an ambulance."
            :link="route('posts.index')"
            link-label="All articles"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach ($posts as $post)
                <x-post-card :post="$post" class="reveal" style="transition-delay: {{ $loop->index * 70 }}ms" />
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
                    <h2 class="h-section text-white">Come and see us</h2>
                    <p class="lede mt-4 text-white/65">
                        {{ setting('address_line') }}, {{ setting('address_city') }}.
                        Outpatient clinics run 8:00 AM to 10:00 PM; the Emergency Department is open every hour of the year.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('contact') }}" class="btn-accent">
                            <x-icon name="map-pin" size="16" /> Directions & contact
                        </a>
                        <a href="tel:{{ setting('hotline') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="grid gap-3">
                        @foreach ([
                            ['ambulance', 'Ambulance', setting('ambulance_number'), 'tel:' . setting('ambulance_number')],
                            ['calendar', 'Appointments', setting('appointment_number'), 'tel:' . setting('appointment_number')],
                            ['globe', 'International desk', setting('international_desk'), 'tel:' . setting('international_desk')],
                        ] as [$icon, $label, $value, $href])
                            <a href="{{ $href }}"
                               class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300">
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
