@extends('layouts.site')

@section('title', 'Appointment Confirmed — ' . $appointment->reference)

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

<section class="section">
    <div class="shell max-w-3xl">

        <div class="card overflow-hidden p-0">
            <div class="flex flex-col items-center gap-4 bg-teal-600 px-8 py-12 text-center text-white">
                <span class="grid h-16 w-16 place-items-center rounded-full bg-white/15">
                    <x-icon name="check" size="34" stroke="2.5" />
                </span>
                <h1 class="font-display text-3xl font-bold text-white">Appointment requested</h1>
                <p class="max-w-md text-white/85">
                    We have your booking. Our appointment desk will confirm by SMS to
                    <span class="font-semibold text-white">{{ $appointment->phone }}</span> shortly.
                </p>
            </div>

            <div class="border-b border-mist-200 bg-mist-50 px-8 py-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-navy-900/50">Reference number</p>
                <p class="mt-1.5 font-display text-3xl font-extrabold tracking-tight text-navy-900">
                    {{ $appointment->reference }}
                </p>
                <p class="mt-2 text-sm text-navy-900/55">Quote this at the reception desk.</p>
            </div>

            <dl class="divide-y divide-mist-200 px-8">
                @foreach ([
                    ['Consultant', $appointment->doctor->name, $appointment->doctor->designation],
                    ['Department', $appointment->doctor->department->name, $appointment->doctor->chamber],
                    ['Date', $appointment->appointment_date->format('l, j F Y'), null],
                    ['Time', $appointment->formattedTime(), 'Please arrive 15 minutes early'],
                    ['Patient', $appointment->patient_name, trim(collect([$appointment->age ? $appointment->age . ' yrs' : null, $appointment->gender ? ucfirst($appointment->gender) : null])->filter()->implode(' · ')) ?: null],
                    ['Consultation fee', '৳' . number_format($appointment->visit_type === 'follow_up' && $appointment->doctor->follow_up_fee ? $appointment->doctor->follow_up_fee : $appointment->doctor->consultation_fee), 'Payable at reception'],
                ] as [$label, $value, $hint])
                    <div class="flex flex-col gap-1 py-5 sm:flex-row sm:items-baseline sm:gap-6">
                        <dt class="w-44 shrink-0 text-sm text-navy-900/50">{{ $label }}</dt>
                        <dd>
                            <span class="font-semibold text-navy-900">{{ $value }}</span>
                            @if ($hint)
                                <span class="mt-0.5 block text-xs text-navy-900/50">{{ $hint }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>

            <div class="flex flex-wrap gap-3 border-t border-mist-200 px-8 py-7">
                <button type="button" onclick="window.print()" class="btn-outline">
                    <x-icon name="file-text" size="16" /> Print this page
                </button>
                <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn-outline">View consultant</a>
                <a href="tel:{{ setting('appointment_number') }}" class="btn-ghost ml-auto">
                    <x-icon name="phone" size="16" /> Need to change it? {{ setting('appointment_number') }}
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="card p-7">
                <h2 class="font-display text-base font-bold text-navy-900">What to bring</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-navy-900/65">
                    @foreach ([
                        'This reference number',
                        'Previous prescriptions, reports and scans',
                        'A list of your current medicines and doses',
                        'National ID or passport for registration',
                    ] as $item)
                        <li class="flex items-start gap-2.5">
                            <x-icon name="check" size="15" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card bg-urgent-50 p-7">
                <div class="flex items-center gap-3">
                    <x-icon name="ambulance" size="22" class="text-urgent-600" />
                    <h2 class="font-display text-base font-bold text-navy-900">If things get worse</h2>
                </div>
                <p class="mt-3 text-sm text-navy-900/65">
                    Do not wait for your appointment date if symptoms become severe. Come to the Emergency
                    Department at any hour, or call for an ambulance.
                </p>
                <a href="tel:{{ setting('hotline') }}" class="btn-urgent mt-5 w-full">
                    <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                </a>
            </div>
        </div>

        <p class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-teal-700 hover:underline">← Back to homepage</a>
        </p>
    </div>
</section>

@endsection
