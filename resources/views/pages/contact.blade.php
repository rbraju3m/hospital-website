@extends('layouts.site')

@section('title', 'Contact & Directions')
@section('meta_description', 'Contact RBR Hospital in Uttara, Dhaka — hotline, appointment desk, ambulance dispatch, international patient desk, address and directions.')

@section('content')

<x-page-hero
    eyebrow="Contact"
    title="Get in touch"
    lede="Call the desk you need directly, or send us a message and we will come back to you within one working day."
    :crumbs="['Contact' => null]" />

{{-- Direct lines --}}
<section class="border-b border-mist-200 bg-mist-50">
    <div class="shell grid gap-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['ambulance', 'Emergency & ambulance', setting('hotline'), 'urgent'],
            ['calendar', 'Appointments', setting('appointment_number'), 'teal'],
            ['globe', 'International patients', setting('international_desk'), 'navy'],
            ['phone', 'General enquiries', setting('emergency_number'), 'navy'],
        ] as [$icon, $label, $number, $tone])
            <a href="tel:{{ $number }}" class="card-interactive group flex items-center gap-4 p-5">
                <span @class([
                    'grid h-11 w-11 shrink-0 place-items-center rounded-xl transition duration-300',
                    'bg-urgent-50 text-urgent-600 group-hover:bg-urgent-600 group-hover:text-white' => $tone === 'urgent',
                    'bg-teal-50 text-teal-700 group-hover:bg-teal-600 group-hover:text-white' => $tone === 'teal',
                    'bg-navy-50 text-navy-700 group-hover:bg-navy-900 group-hover:text-white' => $tone === 'navy',
                ])>
                    <x-icon :name="$icon" size="21" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xs text-navy-900/50">{{ $label }}</span>
                    <span class="block truncate font-display text-base font-bold text-navy-900">{{ $number }}</span>
                </span>
            </a>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">

        {{-- Form --}}
        <div class="lg:col-span-7">
            <h2 class="h-section">Send us a message</h2>
            <p class="mt-4 text-navy-900/60">
                For anything non-urgent — feedback, corporate screening, records requests, billing questions.
                Please do not use this form for medical emergencies.
            </p>

            @if (session('status') === 'contact-sent')
                <div role="status" class="mt-8 flex items-start gap-3 rounded-2xl border border-teal-500/30 bg-teal-50 p-5">
                    <x-icon name="check-circle" size="20" class="mt-0.5 shrink-0 text-teal-700" />
                    <div>
                        <p class="font-semibold text-teal-900">Message received</p>
                        <p class="mt-1 text-sm text-teal-900/75">
                            Thank you. We will reply within one working day.
                        </p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
                @csrf

                <div>
                    <label for="name" class="label">Your name <span class="text-urgent-600">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                           @class(['input', 'input-error' => $errors->has('name')])>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="label">Mobile number <span class="text-urgent-600">*</span></label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                           inputmode="tel" autocomplete="tel" placeholder="01712345678"
                           @class(['input', 'input-error' => $errors->has('phone')])>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="label">Email <span class="text-navy-900/40">(optional)</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email"
                           @class(['input', 'input-error' => $errors->has('email')])>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="subject" class="label">Subject</label>
                    <input id="subject" type="text" name="subject" value="{{ old('subject') }}"
                           placeholder="What is this about?" class="input">
                </div>

                <div class="sm:col-span-2">
                    <label for="message" class="label">Message <span class="text-urgent-600">*</span></label>
                    <textarea id="message" name="message" rows="6" required maxlength="2000"
                              placeholder="How can we help?"
                              @class(['input', 'input-error' => $errors->has('message')])>{{ old('message') }}</textarea>
                    @error('message') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
                    <button type="submit" class="btn-accent btn-lg">
                        <x-icon name="mail" size="18" /> Send message
                    </button>
                    <p class="text-xs text-navy-900/45">
                        For emergencies call
                        <a href="tel:{{ setting('hotline') }}" class="font-semibold text-urgent-600 hover:underline">{{ setting('hotline') }}</a>.
                    </p>
                </div>
            </form>
        </div>

        {{-- Visit info --}}
        <aside class="lg:col-span-5">
            <div class="space-y-4">
                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">Visit us</h2>
                    <dl class="mt-6 space-y-5 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="map-pin" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Address</dt>
                                <dd class="font-medium text-navy-900">
                                    {{ setting('address_line') }}<br>{{ setting('address_city') }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="clock" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Hours</dt>
                                <dd class="font-medium text-navy-900">{{ setting('opening_hours') }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="mail" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Email</dt>
                                <dd class="space-y-0.5">
                                    <a href="mailto:{{ setting('email') }}" class="block font-medium text-navy-900 hover:text-teal-700">{{ setting('email') }}</a>
                                    <a href="mailto:{{ setting('appointment_email') }}" class="block text-navy-900/60 hover:text-teal-700">{{ setting('appointment_email') }}</a>
                                </dd>
                            </div>
                        </div>
                    </dl>

                    <a href="{{ setting('map_url') }}" target="_blank" rel="noopener noreferrer" class="btn-outline mt-6 w-full">
                        <x-icon name="map-pin" size="16" /> Open in maps
                    </a>
                </div>

                <div class="card overflow-hidden p-0">
                    <iframe
                        title="Map showing RBR Hospital, Uttara, Dhaka"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=90.3695%2C23.8689%2C90.3895%2C23.8829&layer=mapnik&marker=23.8759%2C90.3795"
                        class="h-72 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">Getting here</h2>
                    <ul class="mt-5 space-y-3 text-sm text-navy-900/65">
                        @foreach ([
                            'Free patient parking in the basement, entrance from the service road.',
                            'The Emergency Department has a separate ground-floor entrance on Tower A.',
                            'Wheelchair access and assistance available at both entrances.',
                            'Wheelchairs and porters can be requested at the main reception.',
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
