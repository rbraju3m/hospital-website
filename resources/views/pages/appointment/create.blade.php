@extends('layouts.site')

@section('title', 'Book an Appointment')
@section('meta_description', 'Book an appointment online with a specialist consultant at RBR Hospital. Choose your department, doctor, date and time slot in a few steps.')

@section('content')

<x-page-hero
    eyebrow="Online Booking"
    title="Book an appointment"
    lede="Choose a department, pick your consultant, and confirm a time. You will get a reference number immediately — no payment is taken online."
    :crumbs="['Book Appointment' => null]" />

<section class="section">
    <div class="shell grid gap-10 lg:grid-cols-12">

        <div class="lg:col-span-8">

            @if ($errors->any())
                <div role="alert" class="mb-8 rounded-2xl border border-urgent-500/30 bg-urgent-50 p-5">
                    <p class="flex items-center gap-2 font-semibold text-urgent-700">
                        <x-icon name="x" size="18" /> Please check the following
                    </p>
                    <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-urgent-700/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('appointment.store') }}"
                  x-data="appointmentBooking({
                      doctorId: {{ old('doctor_id', $doctor?->id) ?: 'null' }},
                      department: @js(old('department', $selectedDepartment)),
                      date: @js(old('appointment_date', request('date'))),
                      time: @js(old('appointment_time')),
                      initialDates: @js($availableDates),
                  })"
                  x-init="init()"
                  class="space-y-5">
                @csrf

                <input type="hidden" name="doctor_id" :value="doctorId">
                <input type="hidden" name="appointment_date" :value="date">
                <input type="hidden" name="appointment_time" :value="time">

                {{-- ---------- STEP 1: department + doctor ---------- --}}
                <section class="card p-7 sm:p-8">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-navy-900 font-display text-sm font-bold text-white">1</span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">Choose your consultant</h2>
                            <p class="text-sm text-navy-900/55">Filter by department, then select a doctor.</p>
                        </div>
                    </header>

                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="department" class="label">Department</label>
                            <select id="department" x-model="department" @change="loadDoctors()" class="input">
                                <option value="">All departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="doctor" class="label">
                                Consultant
                                <span x-show="loadingDoctors" x-cloak class="ml-1 text-xs font-normal text-navy-900/45">loading…</span>
                            </label>
                            <select id="doctor" x-model.number="doctorId" @change="onDoctorChange()"
                                    class="input" :disabled="loadingDoctors">
                                <option :value="null">Select a consultant</option>
                                <template x-for="doc in doctors" :key="doc.id">
                                    <option :value="doc.id" x-text="`${doc.name} — ${doc.speciality}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <template x-if="selectedDoctor">
                        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl bg-mist-50 px-5 py-4 text-sm">
                            <span class="font-semibold text-navy-900" x-text="selectedDoctor.name"></span>
                            <span class="text-navy-900/60" x-text="selectedDoctor.designation"></span>
                            <span class="ml-auto font-display font-bold text-navy-900"
                                  x-text="`৳${Number(selectedDoctor.consultation_fee).toLocaleString()}`"></span>
                        </div>
                    </template>
                </section>

                {{-- ---------- STEP 2: date + slot ---------- --}}
                <section class="card p-7 sm:p-8" :class="!doctorId && 'opacity-55'">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition"
                              :class="doctorId ? 'bg-navy-900 text-white' : 'bg-mist-100 text-navy-900/40'">2</span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">Pick a date and time</h2>
                            <p class="text-sm text-navy-900/55">Only dates with open slots are shown.</p>
                        </div>
                    </header>

                    <p x-show="!doctorId" class="mt-6 text-sm text-navy-900/45">Select a consultant first.</p>

                    <div x-show="doctorId" x-cloak class="mt-7 space-y-7">
                        {{-- Date chips --}}
                        <div>
                            <p class="label">Available dates</p>

                            <p x-show="loadingSlots" class="text-sm text-navy-900/45">Checking availability…</p>

                            <p x-show="!loadingSlots && dates.length === 0" x-cloak
                               class="rounded-xl bg-mist-50 px-5 py-4 text-sm text-navy-900/60">
                                No open slots in the next three weeks. Please call
                                <a href="tel:{{ setting('appointment_number') }}" class="font-semibold text-teal-700 hover:underline">
                                    {{ setting('appointment_number') }}</a>.
                            </p>

                            <div x-show="!loadingSlots && dates.length" class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                                <template x-for="day in dates" :key="day.date">
                                    <button type="button" @click="selectDate(day.date)"
                                            class="shrink-0 rounded-xl border px-4 py-3 text-center transition"
                                            :class="date === day.date
                                                ? 'border-teal-600 bg-teal-600 text-white shadow-soft'
                                                : 'border-mist-200 bg-white text-navy-900 hover:border-teal-300 hover:bg-teal-50'">
                                        <span class="block text-[11px] font-medium opacity-70" x-text="day.weekday"></span>
                                        <span class="block font-display text-sm font-bold" x-text="day.label"></span>
                                        <span class="block text-[10px] opacity-70" x-text="`${day.slots} open`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Slot grid --}}
                        <div x-show="date" x-cloak>
                            <p class="label">Available times</p>

                            <p x-show="!loadingSlots && slots.length === 0" class="text-sm text-navy-900/50">
                                No slots left on this date — please choose another.
                            </p>

                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                                <template x-for="slot in slots" :key="slot.time">
                                    <button type="button" @click="time = slot.time"
                                            class="rounded-xl border px-3 py-2.5 text-sm font-medium transition"
                                            :class="time === slot.time
                                                ? 'border-teal-600 bg-teal-600 text-white shadow-soft'
                                                : 'border-mist-200 bg-white text-navy-900 hover:border-teal-300 hover:bg-teal-50'"
                                            x-text="slot.label"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ---------- STEP 3: patient details ---------- --}}
                <section class="card p-7 sm:p-8" :class="!time && 'opacity-55'">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold transition"
                              :class="time ? 'bg-navy-900 text-white' : 'bg-mist-100 text-navy-900/40'">3</span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">Patient details</h2>
                            <p class="text-sm text-navy-900/55">We only ask for what the clinic needs.</p>
                        </div>
                    </header>

                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="patient_name" class="label">Patient's full name <span class="text-urgent-600">*</span></label>
                            <input id="patient_name" type="text" name="patient_name" value="{{ old('patient_name') }}"
                                   required autocomplete="name" placeholder="As it should appear on the prescription"
                                   @class(['input', 'input-error' => $errors->has('patient_name')])>
                            @error('patient_name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="label">Mobile number <span class="text-urgent-600">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                                   required inputmode="tel" autocomplete="tel" placeholder="01712345678"
                                   @class(['input', 'input-error' => $errors->has('phone')])>
                            @error('phone')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-navy-900/45">We send the confirmation SMS to this number.</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="label">Email <span class="text-navy-900/40">(optional)</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   autocomplete="email" placeholder="you@example.com"
                                   @class(['input', 'input-error' => $errors->has('email')])>
                            @error('email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="gender" class="label">Gender</label>
                            <select id="gender" name="gender" class="input">
                                <option value="">Prefer not to say</option>
                                @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="age" class="label">Age</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}" min="0" max="120"
                                   placeholder="Years" class="input">
                        </div>

                        <div class="sm:col-span-2">
                            <fieldset>
                                <legend class="label">Visit type</legend>
                                <div class="flex flex-wrap gap-3">
                                    @foreach (['new' => 'First visit', 'follow_up' => 'Follow-up'] as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-mist-200 px-4 py-2.5 text-sm
                                                      transition hover:border-teal-300 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                                            <input type="radio" name="visit_type" value="{{ $value }}"
                                                   @checked(old('visit_type', 'new') === $value)
                                                   class="h-4 w-4 border-mist-200 text-teal-600 focus:ring-teal-500/30">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="notes" class="label">Reason for visit <span class="text-navy-900/40">(optional)</span></label>
                            <textarea id="notes" name="notes" rows="3" maxlength="1000"
                                      placeholder="Main symptoms, how long they have lasted, and any current medication."
                                      class="input">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Submit --}}
                <div class="flex flex-col gap-4 rounded-[1.25rem] border border-mist-200 bg-mist-50 p-7 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-navy-900/60">
                        <template x-if="selectedDoctor && date && time">
                            <p>
                                <span class="font-semibold text-navy-900" x-text="selectedDoctor.name"></span>
                                · <span x-text="prettyDate"></span>
                                at <span class="font-semibold text-navy-900" x-text="prettyTime"></span>
                            </p>
                        </template>
                        <template x-if="!(selectedDoctor && date && time)">
                            <p>Complete the steps above to confirm.</p>
                        </template>
                        <p class="mt-1 text-xs text-navy-900/45">No payment is taken online. Pay at the reception desk.</p>
                    </div>

                    <button type="submit" class="btn-accent btn-lg shrink-0" :disabled="!(doctorId && date && time)">
                        <x-icon name="calendar-check" size="18" />
                        Confirm appointment
                    </button>
                </div>
            </form>
        </div>

        {{-- Side panel --}}
        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                @if ($doctor)
                    <div class="card p-7">
                        <p class="eyebrow">Selected consultant</p>
                        <div class="mt-4 flex items-start gap-4">
                            <x-doctor-avatar :doctor="$doctor" size="sm" />
                            <div class="min-w-0">
                                <h2 class="font-display text-base font-bold text-navy-900">{{ $doctor->name }}</h2>
                                <p class="mt-0.5 text-sm text-teal-700">{{ $doctor->speciality }}</p>
                                <p class="mt-1 text-xs text-navy-900/50">{{ $doctor->department->name }}</p>
                            </div>
                        </div>
                        <a href="{{ route('doctors.show', $doctor) }}"
                           class="mt-5 block text-sm font-semibold text-teal-700 hover:underline">View full profile →</a>
                    </div>
                @endif

                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">Before you come</h2>
                    <ul class="mt-5 space-y-3 text-sm text-navy-900/65">
                        @foreach ([
                            'Arrive 15 minutes early to complete registration.',
                            'Bring any previous prescriptions, reports and scans.',
                            'Bring a list of medicines you currently take, including doses.',
                            'Fasting is only needed if your doctor has told you so.',
                        ] as $tip)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check" size="15" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                                {{ $tip }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card bg-urgent-50 p-7">
                    <div class="flex items-center gap-3">
                        <x-icon name="ambulance" size="22" class="text-urgent-600" />
                        <h2 class="font-display text-base font-bold text-navy-900">This is not for emergencies</h2>
                    </div>
                    <p class="mt-3 text-sm text-navy-900/65">
                        If symptoms are severe or sudden, do not book — come straight to the Emergency Department
                        or call an ambulance.
                    </p>
                    <a href="tel:{{ setting('hotline') }}" class="btn-urgent mt-5 w-full">
                        <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentBooking', (initial) => ({
            department: initial.department || '',
            doctors: [],
            doctorId: initial.doctorId,
            dates: initial.initialDates || [],
            date: initial.date || '',
            slots: [],
            time: initial.time || '',
            loadingDoctors: false,
            loadingSlots: false,

            get selectedDoctor() {
                return this.doctors.find((d) => d.id === this.doctorId) || null;
            },

            get prettyDate() {
                if (!this.date) return '';
                return new Date(`${this.date}T00:00:00`).toLocaleDateString('en-GB', {
                    weekday: 'long', day: 'numeric', month: 'long',
                });
            },

            get prettyTime() {
                const slot = this.slots.find((s) => s.time === this.time);
                return slot ? slot.label : this.time;
            },

            async init() {
                await this.loadDoctors({ keepSelection: true });
                if (this.doctorId) await this.loadSlots();
            },

            async loadDoctors({ keepSelection = false } = {}) {
                this.loadingDoctors = true;
                if (!keepSelection) {
                    this.doctorId = null;
                    this.resetSchedule();
                }

                try {
                    const url = new URL('{{ route('appointment.doctors') }}', window.location.origin);
                    if (this.department) url.searchParams.set('department', this.department);

                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    this.doctors = response.ok ? (await response.json()).doctors : [];
                } catch {
                    this.doctors = [];
                } finally {
                    this.loadingDoctors = false;
                }
            },

            async onDoctorChange() {
                this.resetSchedule();
                if (this.doctorId) await this.loadSlots();
            },

            resetSchedule() {
                this.dates = [];
                this.slots = [];
                this.date = '';
                this.time = '';
            },

            async selectDate(value) {
                this.date = value;
                this.time = '';
                await this.loadSlots();
            },

            async loadSlots() {
                if (!this.doctorId) return;

                this.loadingSlots = true;
                try {
                    const url = new URL('{{ route('appointment.slots') }}', window.location.origin);
                    url.searchParams.set('doctor_id', this.doctorId);
                    url.searchParams.set('date', this.date || new Date().toISOString().slice(0, 10));

                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('slot lookup failed');

                    const payload = await response.json();
                    this.dates = payload.dates;

                    // Default to the first date that actually has openings.
                    if (!this.date && this.dates.length) {
                        this.date = this.dates[0].date;
                        return this.loadSlots();
                    }

                    this.slots = payload.slots;
                } catch {
                    this.slots = [];
                } finally {
                    this.loadingSlots = false;
                }
            },
        }));
    });
</script>
@endpush

@endsection
