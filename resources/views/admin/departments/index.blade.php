@extends('admin.layouts.app')

@section('title', __('admin.nav.departments'))
@section('heading', __('admin.nav.departments'))
@section('subheading', trans_choice('admin.departments.count', $departments->total(), ['count' => number_format($departments->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.departments.create')"
                         :create-label="__('admin.departments.create')"
                         :placeholder="__('admin.departments.search')" />

    <div class="admin-card overflow-hidden">
        @if ($departments->isEmpty())
            <x-admin.empty :message="__('admin.departments.empty')"
                           :action="__('admin.departments.create')"
                           :href="route('admin.departments.create')"
                           icon="building" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.name') }}</th>
                            <th class="admin-th">{{ __('admin.nav.doctors') }}</th>
                            <th class="admin-th">{{ __('admin.form.languages') }}</th>
                            <th class="admin-th">{{ __('admin.fields.sort_order') }}</th>
                            <th class="admin-th">{{ __('admin.fields.is_active') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700">
                                            <x-icon :name="$department->icon" size="17" />
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.departments.edit', $department) }}"
                                               class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                                {{ $department->untranslated('name') }}
                                            </a>
                                            <span class="block truncate text-xs text-navy-900/45">/{{ $department->slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-td">{{ $department->doctors_count }}</td>
                                <td class="admin-td"><x-admin.translation-state :model="$department" /></td>
                                <td class="admin-td">{{ $department->sort_order }}</td>
                                <td class="admin-td">
                                    @if ($department->is_active)
                                        <span class="badge-teal">{{ __('admin.states.active') }}</span>
                                    @else
                                        <span class="badge-slate">{{ __('admin.states.hidden') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('departments.show', $department) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.departments.edit', $department) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.departments.destroy', $department)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $departments->links() }}</div>
@endsection
