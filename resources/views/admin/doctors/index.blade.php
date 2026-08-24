@extends('admin.layouts.app')

@section('title', __('admin.nav.doctors'))
@section('heading', __('admin.nav.doctors'))
@section('subheading', trans_choice('admin.doctors.count', $doctors->total(), ['count' => number_format($doctors->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.doctors.create')"
                         :create-label="__('admin.doctors.create')"
                         :placeholder="__('admin.doctors.search')">
        <select name="department" class="input input-sm w-auto">
            <option value="">{{ __('admin.doctors.all_departments') }}</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department') == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="active" class="input input-sm w-auto">
            <option value="">{{ __('admin.states.any') }}</option>
            <option value="1" @selected(request('active') === '1')>{{ __('admin.states.active') }}</option>
            <option value="0" @selected(request('active') === '0')>{{ __('admin.states.hidden') }}</option>
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden"
         x-data="adminList({ list: 'doctors', sortable: true,
             labels: { saved: @js(__('admin.lists.saved')), failed: @js(__('admin.lists.failed')) } })">
        <x-admin.list-status />

        @if ($doctors->isEmpty())
            <x-admin.empty :message="__('admin.doctors.empty')"
                           :action="__('admin.doctors.create')"
                           :href="route('admin.doctors.create')"
                           icon="stethoscope" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th w-8"></th>
                            <th class="admin-th">{{ __('admin.fields.name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.department_id') }}</th>
                            <th class="admin-th">{{ __('admin.fields.consultation_fee') }}</th>
                            <th class="admin-th">{{ __('admin.doctors.chambers') }}</th>
                            <th class="admin-th">{{ __('admin.lists.live') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($doctors as $doctor)
                            <tr class="admin-row" data-id="{{ $doctor->id }}"
                                @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
                                @dragend="dragEnd()">
                                <td class="admin-td w-8 px-2"><x-admin.drag-handle /></td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-3">
                                        <x-doctor-avatar :doctor="$doctor" size="sm" class="!h-10 !w-10 !text-xs" />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.doctors.edit', $doctor) }}"
                                               class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                                {{ $doctor->untranslated('name') }}
                                            </a>
                                            <span class="block truncate text-xs text-navy-900/45">
                                                {{ $doctor->untranslated('speciality') ?: $doctor->untranslated('designation') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-td">{{ $doctor->department?->untranslated('name') }}</td>
                                <td class="admin-td">৳{{ number_format($doctor->consultation_fee) }}</td>
                                <td class="admin-td">
                                    @if ($doctor->schedules_count)
                                        <span class="badge-navy">{{ trans_choice('admin.doctors.chamber_count', $doctor->schedules_count, ['count' => $doctor->schedules_count]) }}</span>
                                    @else
                                        <span class="badge-amber">{{ __('admin.doctors.no_chamber') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-2">
                                        <x-admin.live-switch :model="$doctor" />
                                        <x-admin.translation-state :model="$doctor" compact />
                                    </div>
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('doctors.show', $doctor) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.doctors.edit', $doctor) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.doctors.destroy', $doctor)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $doctors->links() }}</div>
@endsection
