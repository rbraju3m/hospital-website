@extends('admin.layouts.app')

@section('title', $appointment->reference)
@section('heading', $appointment->patient_name)
@section('subheading', $appointment->reference)

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.appointments.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.section :title="__('admin.appointments.detail')">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    @php
                        $rows = [
                            __('admin.fields.doctor_id') => $appointment->doctor?->name,
                            __('admin.fields.department_id') => $appointment->department?->name,
                            __('admin.fields.appointment_date') => $appointment->appointment_date->translatedFormat('l, j F Y'),
                            __('admin.fields.appointment_time') => $appointment->formattedTime(),
                            __('admin.fields.visit_type') => __("admin.visit.{$appointment->visit_type}"),
                            __('admin.fields.source') => $appointment->source,
                            __('admin.fields.phone') => $appointment->phone,
                            __('admin.fields.email') => $appointment->email,
                            __('admin.fields.age') => $appointment->age,
                            __('admin.fields.gender') => $appointment->gender ? __("admin.gender.{$appointment->gender}") : null,
                            __('admin.appointments.booked_at') => $appointment->created_at->translatedFormat('j M Y, g:i A'),
                        ];
                    @endphp

                    @foreach ($rows as $label => $value)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-navy-900/40">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-navy-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($appointment->notes)
                    <div class="mt-5 rounded-xl bg-mist-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-navy-900/40">{{ __('admin.fields.notes') }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-navy-900/75">{{ $appointment->notes }}</p>
                    </div>
                @endif
            </x-admin.section>

            @if ($sameDay->isNotEmpty())
                <x-admin.section :title="__('admin.appointments.same_day')" :padded="false">
                    <ul>
                        @foreach ($sameDay as $other)
                            <li class="flex items-center gap-4 border-b border-mist-100 px-5 py-3 last:border-0">
                                <span class="w-20 shrink-0 font-display text-sm font-bold text-navy-900">{{ $other->formattedTime() }}</span>
                                <a href="{{ route('admin.appointments.show', $other) }}"
                                   class="min-w-0 flex-1 truncate text-sm text-navy-900/75 hover:text-teal-700">
                                    {{ $other->patient_name }}
                                </a>
                                <x-admin.status-badge :status="$other->status" />
                            </li>
                        @endforeach
                    </ul>
                </x-admin.section>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.section :title="__('admin.fields.status')">
                <div class="mb-4"><x-admin.status-badge :status="$appointment->status" /></div>

                {{-- What the patient did to this themselves. The desk's next
                     move differs: a slot given up is one to offer somebody
                     else, a slot the desk cancelled is a patient somebody may
                     still need to ring. --}}
                @if ($appointment->cancelled_by === 'patient')
                    <p class="mb-4 flex items-start gap-2 rounded-lg bg-mist-50 px-3 py-2 text-xs text-navy-900/60">
                        <x-icon name="info" size="14" class="mt-0.5 shrink-0" />
                        {{ __('admin.appointments.cancelled_by_patient') }}
                    </p>
                @elseif ($appointment->rescheduled_at)
                    <p class="mb-4 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        <x-icon name="info" size="14" class="mt-0.5 shrink-0" />
                        {{ __('admin.appointments.moved_by_patient', [
                            'when' => $appointment->rescheduled_at->translatedFormat('j M, H:i'),
                        ]) }}
                    </p>
                @endif

                <div class="grid gap-2">
                    @foreach (['confirmed', 'completed', 'pending', 'cancelled'] as $status)
                        @continue($status === $appointment->status)
                        <form method="POST" action="{{ route('admin.appointments.status', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit" class="btn-outline btn-sm w-full">
                                {{ __("admin.appointments.mark.{$status}") }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </x-admin.section>

            <x-admin.section :title="__('admin.appointments.edit')" :description="__('admin.appointments.edit_help')">
                <a href="{{ route('admin.appointments.edit', $appointment) }}" class="btn-outline btn-sm w-full">
                    <x-icon name="pencil" size="15" />
                    {{ __('admin.appointments.edit') }}
                </a>
            </x-admin.section>

            <x-admin.section :title="__('admin.appointments.contact_patient')">
                <div class="grid gap-2">
                    <a href="tel:{{ $appointment->phone }}" class="btn-accent btn-sm">
                        <x-icon name="phone" size="15" />
                        {{ $appointment->phone }}
                    </a>
                    @if ($appointment->email)
                        <a href="mailto:{{ $appointment->email }}" class="btn-outline btn-sm">
                            <x-icon name="mail" size="15" />
                            {{ __('admin.appointments.email_patient') }}
                        </a>
                    @endif
                </div>
            </x-admin.section>

            <x-admin.danger-zone :action="route('admin.appointments.destroy', $appointment)"
                                 class="!mt-6"
                                 :description="__('admin.appointments.delete_help')" />
        </div>
    </div>
@endsection
