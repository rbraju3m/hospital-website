@extends('admin.layouts.app')

@section('title', __('admin.nav.services'))
@section('heading', __('admin.nav.services'))
@section('subheading', trans_choice('admin.services.count', $services->total(), ['count' => number_format($services->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.services.create')"
                         :create-label="__('admin.services.create')"
                         :placeholder="__('admin.services.search')">
        <select name="category" class="input input-sm w-auto">
            <option value="">{{ __('admin.services.all_categories') }}</option>
            @foreach (\App\Http\Requests\Admin\ServiceRequest::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>
                    {{ __("services.groups.{$category}") }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($services->isEmpty())
            <x-admin.empty :message="__('admin.services.empty')" :action="__('admin.services.create')"
                           :href="route('admin.services.create')" icon="activity" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.category') }}</th>
                            <th class="admin-th">{{ __('admin.form.languages') }}</th>
                            <th class="admin-th">{{ __('admin.fields.sort_order') }}</th>
                            <th class="admin-th">{{ __('admin.fields.is_active') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700">
                                            <x-icon :name="$service->icon" size="17" />
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.services.edit', $service) }}"
                                               class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                                {{ $service->untranslated('name') }}
                                            </a>
                                            <span class="block truncate text-xs text-navy-900/45">/{{ $service->slug }}</span>
                                        </div>
                                        @if ($service->is_247)
                                            <span class="badge-navy">24/7</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="admin-td">{{ __("services.groups.{$service->category}") }}</td>
                                <td class="admin-td"><x-admin.translation-state :model="$service" /></td>
                                <td class="admin-td">{{ $service->sort_order }}</td>
                                <td class="admin-td">
                                    @if ($service->is_active)
                                        <span class="badge-teal">{{ __('admin.states.active') }}</span>
                                    @else
                                        <span class="badge-slate">{{ __('admin.states.hidden') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('services.show', $service) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.services.edit', $service) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.services.destroy', $service)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $services->links() }}</div>
@endsection
