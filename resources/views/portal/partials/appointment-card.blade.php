@props(['appointment'])

@php
    $style = [
        'pending' => 'bg-amber-50 text-amber-700',
        'confirmed' => 'bg-teal-50 text-teal-800',
        'completed' => 'bg-navy-50 text-navy-700',
        'cancelled' => 'bg-mist-100 text-navy-900/55',
    ][$appointment->status] ?? 'bg-mist-100 text-navy-900/55';
@endphp

<div class="group flex flex-wrap items-center gap-4 border-t border-mist-200 px-5 py-4
            transition duration-200 ease-out first:border-0 hover:bg-mist-50">
    <div class="w-24 shrink-0">
        <p class="font-display text-sm font-bold text-navy-900">
            {{ $appointment->appointment_date->translatedFormat('j M') }}
        </p>
        <p class="text-xs text-navy-900/50">{{ $appointment->formattedTime() }}</p>
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-navy-900">{{ $appointment->doctor?->name }}</p>
        <p class="truncate text-xs text-navy-900/50">
            {{ $appointment->doctor?->department?->name }}
            <span class="mx-1">·</span>
            {{ __('portal.appointments.reference') }} <span class="font-mono">{{ $appointment->reference }}</span>
        </p>
    </div>

    <span class="{{ $style }} inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold">
        {{ __("portal.status.{$appointment->status}") }}
    </span>

    {{-- Only on a booking the patient can still act on: past visits and
         cancelled ones are a record, and a button that refuses is worse than
         no button. --}}
    @if ($appointment->isChangeableByPatient())
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('portal.appointments.reschedule', $appointment) }}"
               class="btn-outline btn-sm">
                <x-icon name="calendar" size="14" />
                {{ __('portal.appointments.change') }}
            </a>

            <form method="POST" action="{{ route('portal.appointments.cancel', $appointment) }}"
                  data-confirm="{{ __('portal.appointments.cancel_confirm') }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="btn btn-sm text-navy-900/50 transition duration-200 hover:text-urgent-700">
                    {{ __('portal.appointments.cancel') }}
                </button>
            </form>
        </div>
    @endif
</div>
