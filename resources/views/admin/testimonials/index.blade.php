@extends('admin.layouts.app')

@section('title', __('admin.nav.testimonials'))
@section('heading', __('admin.nav.testimonials'))
@section('subheading', trans_choice('admin.testimonials.count', $testimonials->total(), ['count' => number_format($testimonials->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.testimonials.create')"
                         :create-label="__('admin.testimonials.create')"
                         :placeholder="__('admin.testimonials.search')" />

    <div class="admin-card overflow-hidden"
         x-data="adminList({ list: 'testimonials', sortable: true,
             labels: { saved: @js(__('admin.lists.saved')), failed: @js(__('admin.lists.failed')) } })">
        <x-admin.list-status />

        @if ($testimonials->isEmpty())
            <x-admin.empty :message="__('admin.testimonials.empty')" :action="__('admin.testimonials.create')"
                           :href="route('admin.testimonials.create')" icon="quote" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th w-8"></th>
                            <th class="admin-th">{{ __('admin.fields.patient_name') }}</th>
                            <th class="admin-th">{{ __('admin.fields.quote') }}</th>
                            <th class="admin-th">{{ __('admin.fields.rating') }}</th>
                            <th class="admin-th">{{ __('admin.lists.live') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($testimonials as $testimonial)
                            <tr class="admin-row" data-id="{{ $testimonial->id }}"
                                @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
                                @dragend="dragEnd()">
                                <td class="admin-td w-8 px-2"><x-admin.drag-handle /></td>
                                <td class="admin-td">
                                    <a href="{{ route('admin.testimonials.edit', $testimonial) }}"
                                       class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                        {{ $testimonial->untranslated('patient_name') }}
                                    </a>
                                    <span class="block truncate text-xs text-navy-900/45">{{ $testimonial->untranslated('treatment') }}</span>
                                </td>
                                <td class="admin-td max-w-sm">
                                    <span class="line-clamp-2 text-xs text-navy-900/60">{{ $testimonial->untranslated('quote') }}</span>
                                </td>
                                <td class="admin-td"><x-rating :rating="$testimonial->rating" /></td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-2">
                                        <x-admin.live-switch :model="$testimonial" />
                                        <x-admin.translation-state :model="$testimonial" compact />
                                    </div>
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.testimonials.edit', $testimonial) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.testimonials.destroy', $testimonial)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $testimonials->links() }}</div>
@endsection
