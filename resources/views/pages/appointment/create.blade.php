@extends('layouts.site')

@section('title', __('appointment.meta_title'))
@section('meta_description', __('appointment.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('appointment.eyebrow')"
    :title="__('appointment.title')"
    :lede="__('appointment.lede')"
    :crumbs="[__('appointment.crumb') => null]" />

<section class="section">
    <div class="shell grid gap-10 lg:grid-cols-12">

        <div class="lg:col-span-8">

            @if ($errors->any())
                <div role="alert" class="alert-danger mb-8 flex-col border-urgent-500/30 p-5">
                    <p class="flex items-center gap-2 font-semibold text-urgent-700">
                        <x-icon name="x" size="18" /> {{ __('appointment.errors_title') }}
                    </p>
                    <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-urgent-700/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless (feature('behaviour_online_booking'))
                {{-- Booking closed from Site controls. The page stays up: it is
                     linked from the header, the footer and the emails already
                     sent, and a visitor who lands here needs to be told how to
                     book rather than shown a 404. --}}
                <div role="status" class="alert-warning mb-8 flex-col p-6">
                    <p class="flex items-center gap-2 font-semibold text-amber-900">
                        <x-icon name="info" size="18" /> {{ __('appointment.closed.title') }}
                    </p>
                    <p class="mt-2 text-sm text-amber-900/85">{{ __('appointment.closed.body') }}</p>
                    <a href="tel:{{ setting('appointment_number') }}" class="btn-primary btn-sm mt-4">
                        <x-icon name="phone-call" size="15" />
                        {{ setting('appointment_number') }}
                    </a>
                </div>
            @endunless

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
                {{-- The card of the step you are on is ringed; finished steps swap
                     their number for a tick. Progress you can see without reading. --}}
                <section class="card p-7 transition-all duration-500 ease-out sm:p-8"
                         :class="step === 1 ? 'border-teal-200 ring-4 ring-teal-500/10' : ''">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold
                                     transition-colors duration-300"
                              :class="doctorId ? 'bg-teal-600 text-white' : 'bg-navy-900 dark:bg-navy-100 text-white'">
                            <span x-show="! doctorId">1</span>
                            <span x-show="doctorId" x-cloak x-transition.scale aria-hidden="true">
                                <x-icon name="check" size="16" stroke="3" />
                            </span>
                        </span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">{{ __('appointment.step_1.title') }}</h2>
                            <p class="text-sm text-navy-900/55">{{ __('appointment.step_1.lede') }}</p>
                        </div>
                    </header>

                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="department" class="label">{{ __('appointment.step_1.department') }}</label>
                            <select id="department" x-model="department" @change="loadDoctors()" class="input">
                                <option value="">{{ __('common.all_departments') }}</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="doctor" class="label">
                                {{ __('appointment.step_1.consultant') }}
                                <span x-show="loadingDoctors" x-cloak class="ml-1 text-xs font-normal text-navy-900/45">{{ __('appointment.step_1.loading') }}</span>
                            </label>
                            <select id="doctor" x-model.number="doctorId" @change="onDoctorChange()"
                                    class="input" :disabled="loadingDoctors">
                                <option :value="null">{{ __('appointment.step_1.select_consultant') }}</option>
                                <template x-for="doc in doctors" :key="doc.id">
                                    <option :value="doc.id" x-text="`${doc.name} — ${doc.speciality}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <template x-if="selectedDoctor">
                        <div x-transition.opacity.duration.300ms
                             class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border border-teal-100 bg-teal-50/50 px-5 py-4 text-sm">
                            <span class="font-semibold text-navy-900" x-text="selectedDoctor.name"></span>
                            <span class="text-navy-900/60" x-text="selectedDoctor.designation"></span>
                            <span class="ml-auto font-display font-bold text-navy-900"
                                  x-text="`৳${Number(selectedDoctor.consultation_fee).toLocaleString()}`"></span>
                        </div>
                    </template>
                </section>

                {{-- ---------- STEP 2: date + slot ---------- --}}
                <section class="card p-7 transition-all duration-500 ease-out sm:p-8"
                         :class="{ 'opacity-55': ! doctorId, 'border-teal-200 ring-4 ring-teal-500/10': step === 2 }">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold
                                     transition-colors duration-300"
                              :class="time ? 'bg-teal-600 text-white' : (doctorId ? 'bg-navy-900 dark:bg-navy-100 text-white' : 'bg-mist-100 text-navy-900/40')">
                            <span x-show="! time">2</span>
                            <span x-show="time" x-cloak x-transition.scale aria-hidden="true">
                                <x-icon name="check" size="16" stroke="3" />
                            </span>
                        </span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">{{ __('appointment.step_2.title') }}</h2>
                            <p class="text-sm text-navy-900/55">{{ __('appointment.step_2.lede') }}</p>
                        </div>
                    </header>

                    <p x-show="!doctorId" class="mt-6 text-sm text-navy-900/45">{{ __('appointment.step_2.select_first') }}</p>

                    <div x-show="doctorId" x-cloak class="mt-7 space-y-7">
                        {{-- Date chips --}}
                        <div>
                            <p class="label">{{ __('appointment.step_2.dates_label') }}</p>

                            {{-- A shaped placeholder rather than a line of text: the chips
                                 land where the skeleton already was, so nothing jumps. --}}
                            <div x-show="loadingSlots" class="flex gap-2 overflow-hidden" aria-hidden="true">
                                @for ($i = 0; $i < 6; $i++)
                                    <span class="skeleton h-[68px] w-[86px] shrink-0 rounded-xl"></span>
                                @endfor
                            </div>
                            <p x-show="loadingSlots" class="sr-only" role="status">{{ __('appointment.step_2.checking') }}</p>

                            <p x-show="!loadingSlots && dates.length === 0" x-cloak
                               class="rounded-xl bg-mist-50 px-5 py-4 text-sm text-navy-900/60">
                                {{ __('appointment.step_2.none_in_window') }}
                                <a href="tel:{{ setting('appointment_number') }}" class="font-semibold text-teal-700 hover:underline">
                                    {{ setting('appointment_number') }}</a>.
                            </p>

                            <div x-show="!loadingSlots && dates.length" x-transition.opacity.duration.300ms
                                 class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                                <template x-for="day in dates" :key="day.date">
                                    <button type="button" @click="selectDate(day.date)"
                                            :aria-pressed="date === day.date ? 'true' : 'false'"
                                            class="shrink-0 rounded-xl border px-4 py-3 text-center transition duration-200 ease-out
                                                   hover:-translate-y-0.5 active:scale-95"
                                            :class="date === day.date
                                                ? 'border-teal-600 bg-teal-600 text-white shadow-lift'
                                                : 'border-mist-200 bg-white dark:bg-navy-100 text-navy-900 hover:border-teal-300 hover:bg-teal-50'">
                                        <span class="block text-[11px] font-medium opacity-70" x-text="day.weekday"></span>
                                        <span class="block font-display text-sm font-bold" x-text="day.label"></span>
                                        <span class="block text-[10px] opacity-70" x-text="slotsOpenLabel(day.slots)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Slot grid --}}
                        <div x-show="date" x-cloak>
                            <p class="label">{{ __('appointment.step_2.times_label') }}</p>

                            <p x-show="!loadingSlots && slots.length === 0" class="text-sm text-navy-900/50">
                                {{ __('appointment.step_2.no_times') }}
                            </p>

                            <div x-show="loadingSlots" class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5" aria-hidden="true">
                                @for ($i = 0; $i < 10; $i++)
                                    <span class="skeleton h-[42px] rounded-xl"></span>
                                @endfor
                            </div>

                            <div x-show="! loadingSlots" class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                                <template x-for="slot in slots" :key="slot.time">
                                    <button type="button" @click="time = slot.time"
                                            :aria-pressed="time === slot.time ? 'true' : 'false'"
                                            class="rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-200 ease-out
                                                   hover:-translate-y-0.5 active:scale-95"
                                            :class="time === slot.time
                                                ? 'border-teal-600 bg-teal-600 text-white shadow-lift'
                                                : 'border-mist-200 bg-white dark:bg-navy-100 text-navy-900 hover:border-teal-300 hover:bg-teal-50'"
                                            x-text="slot.label"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ---------- STEP 3: patient details ---------- --}}
                <section class="card p-7 transition-all duration-500 ease-out sm:p-8"
                         :class="{ 'opacity-55': ! time, 'border-teal-200 ring-4 ring-teal-500/10': step === 3 }">
                    <header class="flex items-center gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full font-display text-sm font-bold
                                     transition-colors duration-300"
                              :class="time ? 'bg-navy-900 dark:bg-navy-100 text-white' : 'bg-mist-100 text-navy-900/40'">3</span>
                        <div>
                            <h2 class="font-display text-lg font-bold text-navy-900">{{ __('appointment.step_3.title') }}</h2>
                            <p class="text-sm text-navy-900/55">{{ __('appointment.step_3.lede') }}</p>
                        </div>
                    </header>

                    <div class="mt-7 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="patient_name" class="label">{{ __('appointment.step_3.patient_name') }} <span class="text-urgent-600">*</span></label>
                            <input id="patient_name" type="text" name="patient_name" value="{{ old('patient_name') }}"
                                   required autocomplete="name" placeholder="{{ __('appointment.step_3.patient_name_placeholder') }}"
                                   @class(['input', 'input-error' => $errors->has('patient_name')])>
                            @error('patient_name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="label">{{ __('appointment.step_3.phone') }} <span class="text-urgent-600">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                                   required inputmode="tel" autocomplete="tel" placeholder="01712345678"
                                   @class(['input', 'input-error' => $errors->has('phone')])>
                            @error('phone')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-navy-900/45">{{ __('appointment.step_3.phone_hint') }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="label">{{ __('appointment.step_3.email') }} <span class="text-navy-900/40">{{ __('common.optional') }}</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   autocomplete="email" placeholder="you@example.com"
                                   @class(['input', 'input-error' => $errors->has('email')])>
                            @error('email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="gender" class="label">{{ __('appointment.step_3.gender') }}</label>
                            <select id="gender" name="gender" class="input">
                                <option value="">{{ __('appointment.step_3.gender_unspecified') }}</option>
                                @foreach (['female' => __('appointment.step_3.gender_female'), 'male' => __('appointment.step_3.gender_male'), 'other' => __('appointment.step_3.gender_other')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="age" class="label">{{ __('appointment.step_3.age') }}</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}" min="0" max="120"
                                   placeholder="{{ __('appointment.step_3.age_placeholder') }}" class="input">
                        </div>

                        <div class="sm:col-span-2">
                            <fieldset>
                                <legend class="label">{{ __('appointment.step_3.visit_type') }}</legend>
                                <div class="flex flex-wrap gap-3">
                                    @foreach (['new' => __('appointment.step_3.visit_new'), 'follow_up' => __('appointment.step_3.visit_follow_up')] as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-mist-200 px-4 py-2.5 text-sm
                                                      transition duration-200 ease-out hover:-translate-y-0.5 hover:border-teal-300
                                                      has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50 has-[:checked]:shadow-soft">
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
                            <label for="notes" class="label">{{ __('appointment.step_3.notes') }} <span class="text-navy-900/40">{{ __('common.optional') }}</span></label>
                            <textarea id="notes" name="notes" rows="3" maxlength="1000"
                                      placeholder="{{ __('appointment.step_3.notes_placeholder') }}"
                                      class="input">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Submit --}}
                <div class="flex flex-col gap-4 rounded-[1.25rem] border border-mist-200 bg-mist-50 p-7 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-navy-900/60">
                        <template x-if="selectedDoctor && date && time">
                            <p x-transition.opacity.duration.300ms>
                                <span class="font-semibold text-navy-900" x-text="selectedDoctor.name"></span>
                                · <span x-text="prettyDate"></span>
                                {{ __('appointment.summary_at') }}
                                <span class="font-semibold text-navy-900" x-text="prettyTime"></span>
                            </p>
                        </template>
                        <template x-if="!(selectedDoctor && date && time)">
                            <p>{{ __('appointment.summary_incomplete') }}</p>
                        </template>
                        <p class="mt-1 text-xs text-navy-900/45">{{ __('appointment.no_online_payment') }}</p>
                    </div>

                    <button type="submit" class="btn-accent btn-lg btn-nudge shrink-0" :disabled="!(doctorId && date && time)">
                        <x-icon name="calendar-check" size="18" />
                        {{ __('appointment.confirm') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Side panel --}}
        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                @if ($doctor)
                    <div class="card p-7">
                        <p class="eyebrow">{{ __('appointment.aside.selected') }}</p>
                        <div class="mt-4 flex items-start gap-4">
                            <x-doctor-avatar :doctor="$doctor" size="sm" />
                            <div class="min-w-0">
                                <h2 class="font-display text-base font-bold text-navy-900">{{ $doctor->name }}</h2>
                                <p class="mt-0.5 text-sm text-teal-700">{{ $doctor->speciality }}</p>
                                <p class="mt-1 text-xs text-navy-900/50">{{ $doctor->department->name }}</p>
                            </div>
                        </div>
                        <a href="{{ route('doctors.show', $doctor) }}"
                           class="mt-5 block text-sm font-semibold text-teal-700 hover:underline">{{ __('appointment.aside.view_profile') }}</a>
                    </div>
                @endif

                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">{{ __('appointment.aside.before_title') }}</h2>
                    <ul class="mt-5 space-y-3 text-sm text-navy-900/65">
                        @foreach ([
                            __('appointment.aside.before_1'),
                            __('appointment.aside.before_2'),
                            __('appointment.aside.before_3'),
                            __('appointment.aside.before_4'),
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
                        <h2 class="font-display text-base font-bold text-navy-900">{{ __('appointment.aside.not_emergency_title') }}</h2>
                    </div>
                    <p class="mt-3 text-sm text-navy-900/65">{{ __('appointment.aside.not_emergency_body') }}</p>
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

            /* Which card is "live" — drives the ring on the step being worked on.
               Derived rather than stored so it cannot fall out of sync with the
               fields that actually gate the submit button. */
            get step() {
                if (! this.doctorId) return 1;
                if (! this.time) return 2;
                return 3;
            },

            get prettyDate() {
                if (!this.date) return '';
                return new Date(`${this.date}T00:00:00`).toLocaleDateString(document.documentElement.lang || 'en', {
                    weekday: 'long', day: 'numeric', month: 'long',
                });
            },

            /* ':count' placeholder is substituted client-side so the string
               stays in the lang file rather than being built in JS. */
            slotsOpenLabel(count) {
                return @js(__('appointment.step_2.slots_open_short')).replace(':count', count);
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
