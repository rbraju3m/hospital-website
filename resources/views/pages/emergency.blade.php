@extends('layouts.site')

@section('title', 'Emergency & Ambulance — Open 24 Hours')
@section('meta_description', 'RBR Hospital Emergency Department is open 24 hours with triage in under five minutes, resuscitation bays beside CT, and advanced life-support ambulances.')

@section('content')

<section class="relative overflow-hidden bg-urgent-700 text-white">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.12]"
         style="background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);background-size:64px 64px"></div>

    <div class="shell relative py-16 sm:py-20">
        <nav aria-label="Breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-white/60">
                <li><a href="{{ route('home') }}" class="transition hover:text-white">Home</a></li>
                <li aria-hidden="true"><x-icon name="chevron-right" size="14" /></li>
                <li class="text-white/90">Emergency</li>
            </ol>
        </nav>

        <p class="eyebrow text-white/80"><span class="h-px w-6 bg-white/70"></span> Open every hour of the year</p>
        <h1 class="h-display mt-3 text-white">Emergency &amp; ambulance</h1>
        <p class="lede mt-5 max-w-2xl text-white/80">
            Do not wait for an appointment and do not wait to see if it passes. Come straight in, or call —
            we will start treatment before anyone asks about paperwork.
        </p>

        <div class="mt-9 flex flex-wrap gap-3">
            <a href="tel:{{ setting('hotline') }}" class="btn btn-lg bg-white text-urgent-700 hover:bg-white/90">
                <x-icon name="phone" size="18" /> Call {{ setting('hotline') }}
            </a>
            <a href="tel:{{ setting('ambulance_number') }}"
               class="btn btn-lg border border-white/40 text-white hover:bg-white/10">
                <x-icon name="ambulance" size="18" /> Ambulance {{ setting('ambulance_number') }}
            </a>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">Come in immediately for any of these</h2>
            <p class="mt-4 text-navy-900/60">
                This list is not exhaustive. If you are unsure, call {{ setting('hotline') }} — it costs nothing to ask.
            </p>

            <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'Chest pain or pressure lasting more than a few minutes',
                    'Sudden weakness or numbness on one side of the body',
                    'Sudden difficulty speaking or understanding speech',
                    'Severe difficulty breathing',
                    'Uncontrolled bleeding',
                    'Loss of consciousness or a first seizure',
                    'Severe abdominal pain of sudden onset',
                    'Major injury, burn or road traffic accident',
                    'Suspected poisoning or overdose',
                    'High fever in an infant under three months',
                    'Persistent vomiting with dehydration',
                    'Sudden loss of vision',
                ] as $symptom)
                    <li class="flex items-start gap-3 rounded-xl border border-urgent-500/20 bg-urgent-50 p-4">
                        <x-icon name="activity" size="17" class="mt-0.5 shrink-0 text-urgent-600" />
                        <span class="text-sm text-navy-900/80">{{ $symptom }}</span>
                    </li>
                @endforeach
            </ul>

            <h2 class="mt-16 font-display text-2xl font-bold text-navy-900">What happens when you arrive</h2>
            <ol class="mt-8 space-y-6">
                @foreach ([
                    ['Triage within five minutes', 'A trained nurse assesses you on arrival. The order of treatment is decided by clinical urgency, not by arrival time — which is why someone who came in after you may be seen first.'],
                    ['Immediate assessment', 'Emergency physicians are on site at all hours. Chest pain gets an ECG within minutes; suspected stroke goes straight to CT.'],
                    ['Treatment starts before paperwork', 'Registration and payment discussion happen after the patient is stable. Nobody is turned away at the door.'],
                    ['Admission or discharge', 'If you need admission, the bed and the specialist team are arranged from the emergency department. If not, you leave with a written plan and a follow-up appointment.'],
                ] as $i => [$title, $body])
                    <li class="flex gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-urgent-600 font-display text-sm font-bold text-white">
                            {{ $i + 1 }}
                        </span>
                        <div>
                            <p class="font-semibold text-navy-900">{{ $title }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-navy-900/65">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <aside class="lg:col-span-5">
            <div class="sticky top-24 space-y-4">
                <div class="card border-urgent-500/30 bg-urgent-50 p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">Emergency numbers</h2>
                    <div class="mt-5 space-y-2.5">
                        @foreach ([
                            ['Emergency hotline', setting('hotline')],
                            ['Ambulance dispatch', setting('ambulance_number')],
                            ['Emergency department', setting('emergency_number')],
                        ] as [$label, $number])
                            <a href="tel:{{ $number }}"
                               class="flex items-center justify-between rounded-xl bg-white px-5 py-4 transition hover:bg-white/70">
                                <span>
                                    <span class="block text-xs text-navy-900/50">{{ $label }}</span>
                                    <span class="block font-display text-lg font-bold text-navy-900">{{ $number }}</span>
                                </span>
                                <x-icon name="phone" size="20" class="text-urgent-600" />
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">Calling an ambulance</h2>
                    <p class="mt-3 text-sm text-navy-900/65">
                        Have this ready when you call — it lets the crew prepare on the way:
                    </p>
                    <ul class="mt-5 space-y-2.5 text-sm text-navy-900/70">
                        @foreach ([
                            'Exact address with a nearby landmark',
                            'What happened and when it started',
                            'The patient\'s age and whether they are conscious',
                            'Known conditions and current medicines',
                            'A phone number the crew can call back',
                        ] as $item)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check" size="15" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">Finding us</h2>
                    <p class="mt-3 text-sm text-navy-900/65">
                        The Emergency Department has its own entrance on the ground floor of Tower A,
                        separate from the main outpatient lobby.
                    </p>
                    <p class="mt-4 text-sm font-medium text-navy-900">
                        {{ setting('address_line') }}<br>{{ setting('address_city') }}
                    </p>
                    <a href="{{ setting('map_url') }}" target="_blank" rel="noopener noreferrer" class="btn-outline mt-5 w-full">
                        <x-icon name="map-pin" size="16" /> Open in maps
                    </a>
                </div>
            </div>
        </aside>
    </div>
</section>

@if ($services->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading eyebrow="Round the clock" title="Services available 24 hours" class="reveal" />
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <a href="{{ route('services.show', $service) }}" class="card-interactive group flex flex-col p-7 reveal">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-navy-50 text-navy-800
                                     transition group-hover:bg-navy-900 group-hover:text-white">
                            <x-icon :name="$service->icon" size="24" />
                        </span>
                        <h3 class="mt-5 font-display text-lg font-bold text-navy-900 group-hover:text-teal-700">{{ $service->name }}</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $service->summary }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
