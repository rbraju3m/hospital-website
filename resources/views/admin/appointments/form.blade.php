@extends('admin.layouts.app')

@section('title', __('admin.appointments.create'))
@section('heading', __('admin.appointments.create'))
@section('subheading', __('admin.appointments.create_help'))

@section('content')
<form method="POST" action="{{ route('admin.appointments.store') }}" class="admin-form"
      x-data="frontDeskBooking({
          slotsUrl: '{{ route('appointment.slots') }}',
          doctorId: '{{ old('doctor_id', $selectedDoctor) }}',
          date: '{{ old('appointment_date', $date) }}',
      })">
    @csrf

    <div class="mb-5">
        <a href="{{ route('admin.appointments.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.appointments.slot')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="doctor_id" :label="__('admin.fields.doctor_id')" required
                                :value="$selectedDoctor"
                                :placeholder="__('admin.appointments.pick_doctor')"
                                x-model="doctorId" @change="loadSlots"
                                :options="$doctors->mapWithKeys(fn ($d) => [
                                    $d->id => $d->untranslated('name').' — '.$d->department?->untranslated('name'),
                                ])->all()" />

                <x-admin.input name="appointment_date" type="date" :label="__('admin.fields.appointment_date')" required
                               :value="$date" x-model="date" @change="loadSlots" />

                <x-admin.input name="appointment_time" type="time" :label="__('admin.fields.appointment_time')" required
                               :value="old('appointment_time')" x-model="time"
                               :help="__('admin.appointments.time_help')" />
            </div>

            {{-- Suggestions, not a constraint: the field above accepts any time,
                 because the desk regularly squeezes a patient into the gaps. --}}
            <div class="mt-5" x-show="slots.length || loading" x-cloak>
                <p class="label-sm">{{ __('admin.appointments.free_slots') }}</p>

                <p x-show="loading" class="text-sm text-navy-900/45">{{ __('admin.appointments.loading_slots') }}</p>

                <div class="flex flex-wrap gap-2" x-show="! loading">
                    <template x-for="slot in slots" :key="slot.time">
                        <button type="button" @click="time = slot.time"
                                :class="time === slot.time ? 'bg-navy-900 text-white' : 'bg-mist-100 text-navy-900/70 hover:bg-mist-200'"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                x-text="slot.label"></button>
                    </template>
                </div>
            </div>

            <p class="mt-3 text-xs text-navy-900/45" x-show="! loading && doctorId && date && slots.length === 0" x-cloak>
                {{ __('admin.appointments.no_free_slots') }}
            </p>
        </x-admin.section>

        <x-admin.section :title="__('admin.appointments.patient')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="patient_name" :label="__('admin.fields.patient_name')" required
                               :value="old('patient_name')" class="sm:col-span-2" />
                <x-admin.input name="phone" :label="__('admin.fields.phone')" required :value="old('phone')"
                               placeholder="01XXXXXXXXX" />
                <x-admin.input name="email" type="email" :label="__('admin.fields.email')" :value="old('email')" />
                <x-admin.select name="gender" :label="__('admin.fields.gender')" :value="old('gender')"
                                :placeholder="__('admin.states.unspecified')"
                                :options="['male' => __('admin.gender.male'), 'female' => __('admin.gender.female'), 'other' => __('admin.gender.other')]" />
                <x-admin.input name="age" type="number" :label="__('admin.fields.age')" :value="old('age')" />
                <x-admin.textarea name="notes" :rows="3" :label="__('admin.fields.notes')" :value="old('notes')"
                                  :help="__('admin.appointments.notes_help')" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            {{-- How the booking is recorded, rather than what was booked: the
                 three fields the desk sets once and rarely changes. --}}
            <x-admin.section :title="__('admin.appointments.detail')">
                <div class="grid gap-5">
                    <x-admin.select name="visit_type" :label="__('admin.fields.visit_type')" required
                                    :value="old('visit_type', 'new')"
                                    :options="['new' => __('admin.visit.new'), 'follow_up' => __('admin.visit.follow_up')]" />

                    <x-admin.select name="status" :label="__('admin.fields.status')" required
                                    :value="old('status', 'confirmed')"
                                    :help="__('admin.appointments.status_help')"
                                    :options="collect(['pending', 'confirmed', 'completed'])
                                        ->mapWithKeys(fn ($s) => [$s => __('admin.appointments.status.'.$s)])->all()" />

                    <x-admin.select name="locale" :label="__('admin.fields.locale')" required
                                    :value="old('locale', app()->getLocale())"
                                    :help="__('admin.appointments.locale_help')"
                                    :options="collect(config('app.available_locales'))->map(fn ($meta) => $meta['native'])->all()" />
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.appointments.index')" :submit="__('admin.appointments.book')" />
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('frontDeskBooking', (config) => ({
            doctorId: config.doctorId || '',
            date: config.date || '',
            time: '',
            slots: [],
            loading: false,

            init() {
                this.loadSlots();
            },

            async loadSlots() {
                if (! this.doctorId || ! this.date) {
                    this.slots = [];
                    return;
                }

                this.loading = true;

                try {
                    const url = new URL(config.slotsUrl, window.location.origin);
                    url.searchParams.set('doctor_id', this.doctorId);
                    url.searchParams.set('date', this.date);

                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const payload = await response.json();

                    this.slots = response.ok ? (payload.slots ?? []) : [];
                } catch (error) {
                    // A failed lookup must not block the booking: the time field
                    // is a plain input and stays usable on its own.
                    this.slots = [];
                } finally {
                    this.loading = false;
                }
            },
        }));
    });
</script>
@endpush
