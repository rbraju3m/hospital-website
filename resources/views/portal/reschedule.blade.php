@extends('portal.layouts.app')

@section('title', __('portal.appointments.reschedule_title'))

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('portal.appointments') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
        ← {{ __('portal.appointments.title') }}
    </a>

    <h1 class="mt-4 font-display text-2xl font-bold text-navy-900">{{ __('portal.appointments.reschedule_title') }}</h1>
    <p class="mt-1.5 text-sm text-navy-900/55">
        {{ __('portal.appointments.reschedule_lede', ['doctor' => $appointment->doctor?->name]) }}
    </p>

    <div class="card mt-6 flex flex-wrap items-center gap-4 p-5">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-mist-100 text-navy-900/50">
            <x-icon name="calendar-check" size="20" />
        </span>
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wider text-navy-900/40">
                {{ __('portal.appointments.reschedule_current') }}
            </p>
            <p class="font-display text-sm font-bold text-navy-900">
                {{ $appointment->appointment_date->translatedFormat('l, j F Y') }} · {{ $appointment->formattedTime() }}
            </p>
            <p class="text-xs text-navy-900/50">
                {{ __('portal.appointments.reference') }} <span class="font-mono">{{ $appointment->reference }}</span>
            </p>
        </div>
    </div>

    {{-- The dates come from the server, so a patient with no JavaScript still
         sees which days the consultant sits; the times arrive per day, which
         is the one thing that cannot be known in advance without shipping the
         whole three weeks of the grid. --}}
    <form method="POST" action="{{ route('portal.appointments.move', $appointment) }}"
          x-data="portalReschedule(@js(route('portal.appointments.slots', $appointment)), @js(old('appointment_date')))"
          class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <div class="card p-6">
            <label for="appointment_date" class="label">{{ __('portal.appointments.reschedule_pick_date') }}</label>
            <select id="appointment_date" name="appointment_date" x-model="date" @change="load()" required
                    class="input @error('appointment_date') input-error @enderror">
                <option value="">{{ __('portal.appointments.reschedule_pick_date') }}</option>
                @foreach ($dates as $available)
                    <option value="{{ $available['date'] }}" @selected(old('appointment_date') === $available['date'])>
                        {{ \Illuminate\Support\Carbon::parse($available['date'])->translatedFormat('l, j F') }}
                    </option>
                @endforeach
            </select>
            @error('appointment_date') <p class="field-error">{{ $message }}</p> @enderror

            <div class="mt-6">
                <span class="label">{{ __('portal.appointments.reschedule_pick_time') }}</span>

                <div class="mt-2 flex flex-wrap gap-2" x-show="! loading && slots.length">
                    <template x-for="slot in slots" :key="slot.time">
                        <label class="cursor-pointer">
                            <input type="radio" name="appointment_time" :value="slot.time" x-model="time" class="peer sr-only">
                            <span class="inline-flex items-center rounded-xl border border-mist-200 px-3 py-2 text-sm font-medium
                                         text-navy-900/70 transition duration-200 hover:border-teal-300
                                         peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-800"
                                  x-text="slot.label"></span>
                        </label>
                    </template>
                </div>

                <p x-show="loading" x-cloak class="mt-2 text-sm text-navy-900/45">{{ __('portal.appointments.reschedule_loading') }}</p>
                <p x-show="! loading && date && ! slots.length" x-cloak class="mt-2 text-sm text-navy-900/45">
                    {{ __('portal.appointments.reschedule_no_slots') }}
                </p>

                @error('appointment_time') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <p class="mt-6 text-xs text-navy-900/45">{{ __('portal.appointments.reschedule_note') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-accent" :disabled="! time" :class="! time && 'opacity-50'">
                {{ __('portal.appointments.reschedule_submit') }}
            </button>
            <a href="{{ route('portal.appointments') }}" class="btn text-navy-900/55 hover:text-navy-900">
                {{ __('portal.appointments.reschedule_back') }}
            </a>
        </div>
    </form>
</div>
@endsection
