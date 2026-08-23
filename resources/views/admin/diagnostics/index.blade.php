@extends('admin.layouts.app')

@section('title', __('admin.nav.diagnostics'))
@section('heading', __('admin.nav.diagnostics'))
@section('subheading', trans_choice('admin.diagnostics.count', $tests->total(), ['count' => number_format($tests->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.diagnostics.create')"
                         :create-label="__('admin.diagnostics.create')"
                         :placeholder="__('admin.diagnostics.search')">
        <select name="category" class="input input-sm w-auto">
            <option value="">{{ __('admin.diagnostics.all_categories') }}</option>
            @foreach (\App\Http\Controllers\Web\DiagnosticController::CATEGORIES as $slug)
                <option value="{{ $slug }}" @selected(request('category') === $slug)>
                    {{ category_label('diagnostics', $slug) }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($tests->isEmpty())
            <x-admin.empty :message="__('admin.diagnostics.empty')" :action="__('admin.diagnostics.create')"
                           :href="route('admin.diagnostics.create')" icon="microscope" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[50rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.category') }}</th>
                            <th class="admin-th">{{ __('admin.fields.report_time') }}</th>
                            <th class="admin-th">{{ __('admin.fields.price') }}</th>
                            <th class="admin-th">{{ __('admin.form.languages') }}</th>
                            <th class="admin-th">{{ __('admin.fields.is_active') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tests as $test)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <a href="{{ route('admin.diagnostics.edit', $test) }}"
                                       class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                        {{ $test->untranslated('name') }}
                                    </a>
                                    <span class="flex items-center gap-2 text-xs text-navy-900/45">
                                        @if ($test->code)
                                            <span class="font-mono">{{ $test->code }}</span>
                                        @endif
                                        @if ($test->is_home_collection)
                                            <span class="badge-teal">{{ __('admin.fields.is_home_collection') }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="admin-td">{{ category_label('diagnostics', $test->category) }}</td>
                                <td class="admin-td">{{ $test->untranslated('report_time') }}</td>
                                <td class="admin-td">
                                    <span class="font-semibold text-navy-900">৳{{ number_format($test->effectivePrice()) }}</span>
                                    @if ($test->savingsPercent())
                                        <span class="ms-1.5 text-xs text-navy-900/40 line-through">৳{{ number_format($test->price) }}</span>
                                    @endif
                                </td>
                                <td class="admin-td"><x-admin.translation-state :model="$test" /></td>
                                <td class="admin-td">
                                    @if ($test->is_active)
                                        <span class="badge-teal">{{ __('admin.states.active') }}</span>
                                    @else
                                        <span class="badge-slate">{{ __('admin.states.hidden') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('diagnostics.show', $test) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.diagnostics.edit', $test) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.diagnostics.destroy', $test)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $tests->links() }}</div>
@endsection
