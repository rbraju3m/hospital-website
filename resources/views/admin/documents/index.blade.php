@extends('admin.layouts.app')

@section('title', __('admin.nav.documents'))
@section('heading', __('admin.nav.documents'))
@section('subheading', trans_choice('admin.documents.count', $documents->total(), ['count' => number_format($documents->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.documents.create')"
                         :create-label="__('admin.documents.create')"
                         :placeholder="__('admin.documents.search')">
        <select name="category" class="input input-sm w-auto">
            <option value="">{{ __('admin.documents.all_categories') }}</option>
            @foreach (\App\Models\PatientDocument::CATEGORIES as $slug)
                <option value="{{ $slug }}" @selected(request('category') === $slug)>
                    {{ __("portal.categories.{$slug}") }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($documents->isEmpty())
            <x-admin.empty :message="__('admin.documents.empty')" :action="__('admin.documents.create')"
                           :href="route('admin.documents.create')" icon="file-text" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.title') }}</th>
                            <th class="admin-th">{{ __('admin.fields.phone') }}</th>
                            <th class="admin-th">{{ __('admin.fields.category') }}</th>
                            <th class="admin-th">{{ __('admin.fields.issued_at') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr class="admin-row">
                                <td class="admin-td">
                                    <span class="block truncate font-semibold text-navy-900">{{ $document->title }}</span>
                                    <span class="block truncate text-xs text-navy-900/45">
                                        {{ $document->readableSize() }}
                                        @if ($document->downloaded_at)
                                            · {{ __('admin.documents.downloaded', ['date' => $document->downloaded_at->translatedFormat('j M Y')]) }}
                                        @else
                                            · {{ __('admin.documents.never_downloaded') }}
                                        @endif
                                    </span>
                                </td>
                                <td class="admin-td">
                                    <span class="block font-mono text-xs">0{{ $document->phone }}</span>
                                    @if ($registered->has($document->phone))
                                        <span class="badge-teal">{{ __('admin.documents.registered_as', ['name' => $registered[$document->phone]]) }}</span>
                                    @else
                                        <span class="badge-amber">{{ __('admin.documents.not_registered') }}</span>
                                    @endif
                                </td>
                                <td class="admin-td">{{ __("portal.categories.{$document->category}") }}</td>
                                <td class="admin-td">{{ $document->issued_at?->translatedFormat('j M Y') ?: '—' }}</td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.documents.download', $document) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('portal.documents.download') }}">
                                            <x-icon name="download" size="16" />
                                        </a>
                                        <a href="{{ route('admin.documents.edit', $document) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.documents.destroy', $document)"
                                                             :confirm="__('admin.documents.delete_help')" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $documents->links() }}</div>
@endsection
