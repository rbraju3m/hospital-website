@extends('admin.layouts.app')

@section('title', __('admin.nav.gallery'))
@section('heading', __('admin.nav.gallery'))
@section('subheading', trans_choice('admin.gallery.count', $albums->total(), ['count' => number_format($albums->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.gallery.create')"
                         :create-label="__('admin.gallery.create')"
                         :placeholder="__('admin.gallery.search')" />

    <div class="admin-card overflow-hidden"
         x-data="adminList({ list: 'gallery', sortable: true,
             labels: { saved: @js(__('admin.lists.saved')), failed: @js(__('admin.lists.failed')) } })">
        <x-admin.list-status />

        @if ($albums->isEmpty())
            <x-admin.empty :message="__('admin.gallery.empty')"
                           :action="__('admin.gallery.create')"
                           :href="route('admin.gallery.create')"
                           icon="image" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th w-8"></th>
                            <th class="admin-th">{{ __('admin.fields.title') }}</th>
                            <th class="admin-th">{{ __('admin.fields.photos') }}</th>
                            <th class="admin-th">{{ __('admin.lists.live') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($albums as $album)
                            @php $cover = image_url($album->untranslated('image'), 'cover', $album->id, 'gallery-album'); @endphp
                            <tr class="admin-row" data-id="{{ $album->id }}"
                                @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
                                @dragend="dragEnd()">
                                <td class="admin-td w-8 px-2"><x-admin.drag-handle /></td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-3">
                                        <span class="h-11 w-14 shrink-0 overflow-hidden rounded-lg border border-mist-200 bg-mist-50">
                                            @if ($cover)
                                                <img src="{{ $cover }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <span class="grid h-full w-full place-items-center text-navy-900/25">
                                                    <x-icon name="image" size="16" />
                                                </span>
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.gallery.edit', $album) }}"
                                               class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                                {{ $album->untranslated('title') }}
                                            </a>
                                            <span class="block truncate text-xs text-navy-900/45">/{{ $album->slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-td">{{ $album->photos_count }}</td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-2">
                                        <x-admin.live-switch :model="$album" />
                                        <x-admin.translation-state :model="$album" compact />
                                    </div>
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('gallery.show', $album) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.gallery.edit', $album) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.gallery.destroy', $album)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $albums->links() }}</div>
@endsection
