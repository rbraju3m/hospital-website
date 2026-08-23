@extends('admin.layouts.app')

@section('title', __('admin.nav.appointments'))
@section('heading', __('admin.nav.appointments'))
@section('subheading', trans_choice('admin.appointments.count', $appointments->total(), ['count' => number_format($appointments->total())]))

@section('content')
    {{-- Status counts double as filters: the number a receptionist wants is
         almost always "how many still need confirming". --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.appointments.index') }}"
           class="{{ request()->missing('status') ? 'btn-primary' : 'btn-outline' }} btn-sm">
            {{ __('admin.states.any') }}
            <span class="opacity-60">{{ $counts->sum() }}</span>
        </a>
        @foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status)
            <a href="{{ route('admin.appointments.index', array_merge(request()->except(['status', 'page']), ['status' => $status])) }}"
               class="{{ request('status') === $status ? 'btn-primary' : 'btn-outline' }} btn-sm">
                {{ __("admin.appointments.status.{$status}") }}
                <span class="opacity-60">{{ $counts[$status] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.list-header :create-href="route('admin.appointments.create')"
                         :create-label="__('admin.appointments.create')"
                         :placeholder="__('admin.appointments.search')">
        <input type="date" name="date" value="{{ request('date') }}" class="input input-sm w-auto">

        <select name="doctor" class="input input-sm w-auto">
            <option value="">{{ __('admin.appointments.all_doctors') }}</option>
            @foreach ($doctors as $doctor)
                <option value="{{ $doctor->id }}" @selected(request('doctor') == $doctor->id)>{{ $doctor->name }}</option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($appointments->isEmpty())
            <x-admin.empty :message="__('admin.appointments.empty')" icon="calendar-check" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.reference') }}</th>
                            <th class="admin-th">{{ __('admin.fields.patient_name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.doctor_id') }}</th>
                            <th class="admin-th">{{ __('admin.appointments.when') }}</th>
                            <th class="admin-th">{{ __('admin.fields.status') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appointments as $appointment)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <a href="{{ route('admin.appointments.show', $appointment) }}"
                                       class="font-mono text-xs font-semibold text-navy-900 hover:text-teal-700">
                                        {{ $appointment->reference }}
                                    </a>
                                    @if ($appointment->source !== 'website')
                                        <span class="ms-1 badge-slate">{{ __('admin.appointments.source_desk') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td">
                                    <span class="block truncate font-medium text-navy-900">{{ $appointment->patient_name }}</span>
                                    <a href="tel:{{ $appointment->phone }}" class="block truncate text-xs text-navy-900/45 hover:text-teal-700">
                                        {{ $appointment->phone }}
                                    </a>
                                </td>
                                <td class="admin-td">{{ $appointment->doctor?->name }}</td>
                                <td class="admin-td">
                                    <span class="block">{{ $appointment->appointment_date->translatedFormat('j M Y') }}</span>
                                    <span class="block text-xs text-navy-900/45">{{ $appointment->formattedTime() }}</span>
                                </td>
                                <td class="admin-td"><x-admin.status-badge :status="$appointment->status" /></td>
                                <td class="admin-td text-end">
                                    <a href="{{ route('admin.appointments.show', $appointment) }}"
                                       class="btn-outline btn-sm">{{ __('admin.actions.open') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
        <div>{{ $appointments->links() }}</div>

        <a href="{{ route('admin.appointments.export', request()->query()) }}" class="btn-outline btn-sm">
            <x-icon name="download" size="15" />
            {{ __('admin.appointments.export') }}
        </a>
    </div>
@endsection
