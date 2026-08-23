@extends('layouts.site')

@section('title', 'International Patient Services')
@section('meta_description', 'Medical visa letters, airport pickup, accommodation, interpreters and cost estimates for international patients travelling to RBR Hospital, Dhaka.')

@section('content')

<x-page-hero
    eyebrow="International Patients"
    title="Travelling for treatment, handled end to end"
    lede="A dedicated coordinator manages the practical side — visa letters, transfers, accommodation, interpreters and cost estimates — so you can concentrate on the medicine."
    :crumbs="['International Patients' => null]">

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('contact') }}" class="btn-accent">
            <x-icon name="mail" size="16" /> Request an estimate
        </a>
        <a href="tel:{{ setting('international_desk') }}"
           class="btn border border-white/25 text-white hover:bg-white/10">
            <x-icon name="phone" size="16" /> {{ setting('international_desk') }}
        </a>
    </div>
</x-page-hero>

<section class="section">
    <div class="shell">
        <x-section-heading
            eyebrow="What we arrange"
            title="Support from first enquiry to follow-up at home"
            lede="One coordinator stays with your case throughout, so you are not re-explaining your situation to a different person each time you call."
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['file-text', 'Medical visa invitation', 'A signed invitation letter for the Bangladesh medical visa, issued within two working days of receiving your reports.'],
                ['plane', 'Airport pickup', 'A hospital vehicle meets you at Hazrat Shahjalal International Airport and brings you directly to the hospital or your accommodation.'],
                ['building', 'Accommodation', 'Serviced apartments and hotels within a few minutes of the hospital, at negotiated rates, for patients and accompanying family.'],
                ['globe', 'Interpreter support', 'Interpreters for English, Hindi, Urdu, Arabic and Nepali arranged for consultations and admission.'],
                ['credit-card', 'Cost estimate in advance', 'A written estimate before you travel, covering the procedure, hospital stay and expected investigations.'],
                ['user-round', 'Follow-up after you return', 'Teleconsultation with your treating consultant after discharge, so follow-up does not require a second flight.'],
            ] as [$icon, $title, $body])
                <div class="card reveal flex flex-col p-7" style="transition-delay: {{ $loop->index * 60 }}ms">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-50 text-teal-700">
                        <x-icon :name="$icon" size="24" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section bg-mist-50">
    <div class="shell grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">How it works</h2>
            <ol class="mt-10 space-y-8">
                @foreach ([
                    ['Send us your reports', 'Email your diagnosis, recent reports and scans to the international desk. Nothing needs to be translated first — we will handle that.'],
                    ['Receive an opinion and estimate', 'The relevant consultant reviews your case and we send back a treatment plan with a written cost estimate, usually within three working days.'],
                    ['Visa and travel', 'We issue the medical visa invitation letter and help plan travel dates around the consultant\'s operating schedule.'],
                    ['Arrival and admission', 'You are met at the airport. Registration, admission and initial investigations are coordinated so that they happen in one day rather than three.'],
                    ['Treatment', 'Your coordinator remains your single point of contact throughout the admission, including for family updates.'],
                    ['Return and follow-up', 'You leave with a full discharge summary and imaging on file, and teleconsultation is scheduled for follow-up from home.'],
                ] as $i => [$title, $body])
                    <li class="flex gap-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-navy-900 font-display text-sm font-bold text-white">
                            {{ $i + 1 }}
                        </span>
                        <div>
                            <p class="font-display text-base font-bold text-navy-900">{{ $title }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-navy-900/65">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <aside class="lg:col-span-5">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">International desk</h2>
                    <dl class="mt-6 space-y-5 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="phone" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Direct line (WhatsApp available)</dt>
                                <dd><a href="tel:{{ setting('international_desk') }}" class="font-medium text-navy-900 hover:text-teal-700">{{ setting('international_desk') }}</a></dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="mail" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Email</dt>
                                <dd><a href="mailto:{{ setting('international_email') }}" class="font-medium text-navy-900 hover:text-teal-700">{{ setting('international_email') }}</a></dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="globe" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Languages supported</dt>
                                <dd class="font-medium text-navy-900">English, Bangla, Hindi, Urdu, Arabic, Nepali</dd>
                            </div>
                        </div>
                    </dl>

                    <a href="{{ route('contact') }}" class="btn-accent mt-7 w-full">Send your reports</a>
                </div>

                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">What to send with your enquiry</h2>
                    <ul class="mt-5 space-y-2.5 text-sm text-navy-900/70">
                        @foreach ([
                            'Current diagnosis, if you have one',
                            'Recent blood reports and imaging (CT, MRI, X-ray)',
                            'Discharge summaries from previous admissions',
                            'A list of current medicines with doses',
                            'Patient age, and passport copy for the visa letter',
                        ] as $item)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check" size="15" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</section>

@endsection
