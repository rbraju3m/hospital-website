@extends('layouts.site')

@section('title', 'About RBR Hospital')
@section('meta_description', 'RBR Hospital is a ' . setting('bed_count') . '-bed multidisciplinary hospital in Dhaka, serving patients since ' . setting('established_year') . '. JCI accredited and ISO 9001:2015 certified.')

@section('content')

<x-page-hero
    eyebrow="About Us"
    title="A hospital built around how patients actually move through it"
    lede="Since {{ setting('established_year') }}, RBR Hospital has grown into a {{ setting('bed_count') }}-bed multidisciplinary institution in Uttara — organised so that patients are not left coordinating their own care between departments."
    :crumbs="['About' => null]" />

{{-- Stats --}}
<section class="border-b border-mist-200 bg-mist-50">
    <div class="shell grid grid-cols-2 gap-8 py-12 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['stat_years', 'Years of care'],
            ['stat_beds', 'Inpatient beds'],
            ['stat_icu_beds', 'Critical care beds'],
            ['stat_doctors', 'Consultants'],
            ['stat_departments', 'Departments'],
            ['stat_patients_yearly', 'Patients a year'],
        ] as [$key, $label])
            <div>
                <p class="font-display text-3xl font-extrabold text-navy-900">{{ setting($key) }}</p>
                <p class="mt-1 text-xs font-medium tracking-wide text-navy-900/50">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="shell grid gap-14 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">Our story</h2>
            <div class="mt-6 space-y-4 text-base leading-relaxed text-navy-900/70">
                <p>
                    RBR Hospital opened in {{ setting('established_year') }} with sixty beds and four specialties.
                    The founding intent was straightforward: build a hospital where a patient with a complicated
                    problem would not have to become their own case manager, carrying films and reports between
                    departments that never spoke to one another.
                </p>
                <p>
                    That principle shaped how the hospital grew. Rather than adding specialties as separate silos,
                    we organised the busiest ones into centres of excellence — cardiac sciences, neurosciences,
                    oncology, orthopaedics and others — each combining physicians, surgeons, imaging and
                    rehabilitation into one pathway with shared notes and shared review meetings.
                </p>
                <p>
                    Today the hospital runs {{ setting('bed_count') }} beds, of which
                    {{ setting('stat_icu_beds') }} are critical care, and treats around
                    {{ setting('stat_patients_yearly') }} patients a year. We are JCI accredited and
                    ISO 9001:2015 certified, which matters less as a badge than as an obligation:
                    both require independent audit against standards we cannot quietly relax.
                </p>
                <p>
                    We publish response times, staffing ratios and accreditation status because those are the
                    things that separate hospitals in practice. Marketing language does not.
                </p>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="card p-8">
                <h3 class="font-display text-lg font-bold text-navy-900">What we hold ourselves to</h3>
                <ul class="mt-6 space-y-6">
                    @foreach ([
                        ['ambulance', 'Treat first, paperwork after', 'No emergency patient is asked for payment or documentation before treatment begins.'],
                        ['users', 'One team, one plan', 'Complex cases are reviewed by a multidisciplinary board, not passed between specialists in sequence.'],
                        ['file-text', 'Nothing hidden in the bill', 'Cost estimates are given before planned procedures, and itemised afterwards.'],
                        ['shield-check', 'Audited, not self-assessed', 'JCI accreditation and ISO certification are renewed by external audit, not internal review.'],
                    ] as [$icon, $title, $body])
                        <li class="flex gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700">
                                <x-icon :name="$icon" size="20" />
                            </span>
                            <div>
                                <p class="font-semibold text-navy-900">{{ $title }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-navy-900/60">{{ $body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section bg-navy-900 text-white">
    <div class="shell">
        <x-section-heading
            eyebrow="Mission & Values"
            title="What we are trying to do"
            align="center"
            class="reveal [&_h2]:text-white [&_p.lede]:text-white/65" />

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['heart-pulse', 'Compassion first', 'Illness is frightening. How a patient is spoken to is part of the treatment, not a courtesy layered on top of it.'],
                ['award', 'Clinical excellence', 'Protocols follow international guidelines, and outcomes are audited rather than assumed.'],
                ['globe', 'Access without compromise', 'Generic options are offered alongside branded medicines, and costs are stated up front.'],
            ] as [$icon, $title, $body])
                <div class="reveal rounded-2xl border border-white/10 bg-white/5 p-8"
                     style="transition-delay: {{ $loop->index * 80 }}ms">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-500/15 text-teal-300">
                        <x-icon :name="$icon" size="24" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-bold text-white">{{ $title }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/65">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="card flex flex-col items-center gap-6 p-10 text-center sm:p-14">
            <h2 class="font-display text-2xl font-bold text-navy-900">
                {{ $doctorCount }} consultants across {{ $departmentCount }} departments
            </h2>
            <p class="max-w-xl text-navy-900/60">
                Browse our specialists by department, or book directly with the consultant you need.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('doctors.index') }}" class="btn-accent">Find a doctor</a>
                <a href="{{ route('departments.index') }}" class="btn-outline">Browse departments</a>
            </div>
        </div>
    </div>
</section>

@endsection
