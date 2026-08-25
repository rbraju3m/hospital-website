@extends('admin.layouts.app')

@section('title', __('admin.nav.slides'))
@section('heading', __('admin.nav.slides'))
@section('subheading', trans_choice('admin.slides.count', $slides->total(), ['count' => number_format($slides->total())]))

@section('content')
    {{-- Slides only reach the site while the slider layout is the one on air.
         Saying so here is the difference between a staff member trusting this
         screen and one of them spending an afternoon wondering why their slide
         is not on the home page. --}}
    @unless ($layoutShowsSlider)
        <div class="mb-6 flex flex-wrap items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700">
                <x-icon name="eye-off" size="19" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-900">{{ __('admin.slides.not_showing') }}</p>
                <p class="mt-0.5 text-xs text-amber-900/70">{{ __('admin.slides.not_showing_help') }}</p>
            </div>

            @can('reach', 'site_controls')
                <a href="{{ route('admin.site.edit') }}" class="btn-outline btn-sm shrink-0">
                    <x-icon name="sliders" size="15" />
                    {{ __('admin.nav.site_controls') }}
                </a>
            @endcan
        </div>
    @endunless

    <x-admin.list-header :create-href="route('admin.slides.create')"
                         :create-label="__('admin.slides.create')"
                         :placeholder="__('admin.slides.search')" />

    <div class="admin-card overflow-hidden"
         x-data="adminList({ list: 'slides', sortable: true,
             labels: { saved: @js(__('admin.lists.saved')), failed: @js(__('admin.lists.failed')) } })">
        <x-admin.list-status />

        @if ($slides->isEmpty())
            <x-admin.empty :message="__('admin.slides.empty')" :action="__('admin.slides.create')"
                           :href="route('admin.slides.create')" icon="layers" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th w-8"></th>
                            <th class="admin-th w-28">{{ __('admin.fields.image') }}</th>
                            <th class="admin-th">{{ __('admin.fields.title') }}</th>
                            <th class="admin-th">{{ __('admin.slides.buttons') }}</th>
                            <th class="admin-th">{{ __('admin.lists.live') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slides as $slide)
                            <tr class="admin-row" data-id="{{ $slide->id }}"
                                @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
                                @dragend="dragEnd()">
                                <td class="admin-td w-8 px-2"><x-admin.drag-handle /></td>

                                <td class="admin-td w-28">
                                    @if ($slide->url())
                                        <img src="{{ $slide->url() }}" alt="" loading="lazy"
                                             class="h-12 w-20 rounded-lg border border-mist-200 object-cover">
                                    @else
                                        <span class="grid h-12 w-20 place-items-center rounded-lg border border-dashed border-mist-200 text-navy-900/25">
                                            <x-icon name="image" size="16" />
                                        </span>
                                    @endif
                                </td>

                                <td class="admin-td">
                                    <a href="{{ route('admin.slides.edit', $slide) }}"
                                       class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                        {{ $slide->untranslated('title') }}
                                    </a>
                                    <span class="block truncate text-xs text-navy-900/45">{{ $slide->untranslated('subtitle') }}</span>
                                </td>

                                <td class="admin-td">
                                    <span class="block truncate text-xs text-navy-900/60">{{ $slide->untranslated('cta_label') ?: '—' }}</span>
                                    <span class="block truncate text-[11px] text-navy-900/40">{{ $slide->cta_url }}</span>
                                </td>

                                <td class="admin-td">
                                    <div class="flex items-center gap-2">
                                        <x-admin.live-switch :model="$slide" />
                                        <x-admin.translation-state :model="$slide" compact />
                                    </div>
                                </td>

                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.slides.edit', $slide) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.slides.destroy', $slide)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $slides->links() }}</div>
@endsection
