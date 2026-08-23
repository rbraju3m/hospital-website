@extends('admin.layouts.app')

@section('title', __('admin.nav.packages'))
@section('heading', __('admin.nav.packages'))
@section('subheading', trans_choice('admin.packages.count', $packages->total(), ['count' => number_format($packages->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.packages.create')"
                         :create-label="__('admin.packages.create')"
                         :placeholder="__('admin.packages.search')">
        <select name="category" class="input input-sm w-auto">
            <option value="">{{ __('admin.packages.all_categories') }}</option>
            @foreach (\App\Http\Requests\Admin\HealthPackageRequest::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>
                    {{ category_label('packages', $category) }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($packages->isEmpty())
            <x-admin.empty :message="__('admin.packages.empty')" :action="__('admin.packages.create')"
                           :href="route('admin.packages.create')" icon="package" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.category') }}</th>
                            <th class="admin-th">{{ __('admin.fields.price') }}</th>
                            <th class="admin-th">{{ __('admin.form.languages') }}</th>
                            <th class="admin-th">{{ __('admin.fields.is_active') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $package)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <a href="{{ route('admin.packages.edit', $package) }}"
                                       class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                        {{ $package->untranslated('name') }}
                                    </a>
                                    <span class="block truncate text-xs text-navy-900/45">/{{ $package->slug }}</span>
                                </td>
                                <td class="admin-td">{{ category_label('packages', $package->category) }}</td>
                                <td class="admin-td">
                                    <span class="font-semibold text-navy-900">৳{{ number_format($package->effectivePrice()) }}</span>
                                    @if ($package->savingsPercent())
                                        <span class="ms-1.5 text-xs text-navy-900/40 line-through">৳{{ number_format($package->price) }}</span>
                                    @endif
                                </td>
                                <td class="admin-td"><x-admin.translation-state :model="$package" /></td>
                                <td class="admin-td">
                                    @if ($package->is_active)
                                        <span class="badge-teal">{{ __('admin.states.active') }}</span>
                                    @else
                                        <span class="badge-slate">{{ __('admin.states.hidden') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('packages.show', $package) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.packages.edit', $package) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.packages.destroy', $package)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $packages->links() }}</div>
@endsection
