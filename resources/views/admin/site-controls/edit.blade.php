@extends('admin.layouts.app')

@section('title', __('admin.site_controls.title'))
@section('heading', __('admin.site_controls.title'))
@section('subheading', __('admin.site_controls.intro'))

@section('content')
@php
    $total = count($state);
    $on = count(array_filter($state));
    $groupIcons = ['areas' => 'compass', 'home' => 'layout-dashboard', 'chrome' => 'panel-top', 'behaviour' => 'sliders'];
@endphp

<form method="POST" action="{{ route('admin.site.update') }}"
      x-data="siteControls()" x-init="sync()" @change="sync()" class="admin-form">
    @csrf
    @method('PUT')

    {{-- Summary + filter. The count is live: flipping a switch is meant to feel
         like it registered before the form is even saved. --}}
    <div class="admin-card mb-6 flex flex-wrap items-center gap-4 p-4">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700">
            <x-icon name="sliders" size="20" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-navy-900">
                {{ __('admin.site_controls.summary_heading') }}
            </p>
            <p class="text-xs text-navy-900/50">
                <span x-text="count"></span>/{{ $total }} {{ __('admin.site_controls.summary_on') }}
                <span x-show="dirty" x-cloak class="ms-2 font-semibold text-amber-600">
                    · {{ __('admin.site_controls.unsaved') }}
                </span>
            </p>
        </div>

        <label class="relative w-full sm:w-64">
            <span class="sr-only">{{ __('admin.site_controls.search') }}</span>
            <x-icon name="search" size="16" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-navy-900/35" />
            <input type="search" x-model="query" placeholder="{{ __('admin.site_controls.search') }}"
                   class="input input-sm ps-9">
        </label>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        @foreach ($groups as $group => $keys)
            {{-- Filtering hides rows rather than showing them: with the panel's
                 JavaScript unavailable the page still lists every switch, which
                 is the difference between "no filter" and "no controls". --}}
            <section class="admin-card overflow-hidden" data-control-group
                     :class="{ 'hidden': ! groupVisible($el) }">
                <header class="flex flex-wrap items-center gap-3 border-b border-mist-200 bg-mist-50/60 px-5 py-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-navy-800 shadow-soft">
                        <x-icon :name="$groupIcons[$group] ?? 'sliders'" size="17" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="font-display text-base font-bold text-navy-900">
                            {{ __("admin.site_controls.groups.{$group}") }}
                        </h2>
                        <p class="mt-0.5 text-xs text-navy-900/50">
                            {{ __("admin.site_controls.group_hints.{$group}") }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" class="btn-ghost btn-sm" @click="setGroup($el, true)">
                            {{ __('admin.site_controls.all_on') }}
                        </button>
                        <button type="button" class="btn-ghost btn-sm" @click="setGroup($el, false)">
                            {{ __('admin.site_controls.all_off') }}
                        </button>
                    </div>
                </header>

                <div class="divide-y divide-mist-200/70 p-2">
                    @foreach ($keys as $key => $default)
                        <div data-control-row :class="{ 'hidden': ! matches($el) }"
                             data-label="{{ Str::lower(__("admin.site_controls.keys.{$key}").' '.__("admin.site_controls.hints.{$key}")) }}">
                            <x-admin.switch
                                :name="'features['.$key.']'"
                                :label="__('admin.site_controls.keys.'.$key)"
                                :help="__('admin.site_controls.hints.'.$key)"
                                :value="$state[$key]" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    {{-- Maintenance is the one switch that takes the whole site down, so it says
         so here rather than only in its own hint. --}}
    <p class="mt-6 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
        <x-icon name="alert-triangle" size="15" class="mt-0.5 shrink-0" />
        {{ __('admin.site_controls.staff_preview_note') }}
    </p>

    <x-admin.form-actions :cancel="route('admin.dashboard')" :submit="__('admin.site_controls.save')" />
</form>

@push('scripts')
<script>
    function siteControls() {
        return {
            query: '',
            count: 0,
            dirty: false,
            initial: '',

            sync() {
                const boxes = [...this.$el.querySelectorAll('[data-site-switch]')];
                this.count = boxes.filter((box) => box.checked).length;
                const state = boxes.map((box) => (box.checked ? 1 : 0)).join('');
                if (this.initial === '') this.initial = state;
                this.dirty = state !== this.initial;
            },

            // Rows filter on their own label and hint text, so "book" finds both
            // the booking area and the header's book button.
            matches(row) {
                if (! this.query.trim()) return true;
                return row.dataset.label.includes(this.query.trim().toLowerCase());
            },

            groupVisible(group) {
                return [...group.querySelectorAll('[data-control-row]')].some((row) => this.matches(row));
            },

            setGroup(group, on) {
                group.querySelectorAll('[data-site-switch]').forEach((box) => {
                    if (box.closest('[data-control-row]') && ! this.matches(box.closest('[data-control-row]'))) return;
                    box.checked = on;
                });
                this.sync();
            },
        };
    }
</script>
@endpush
@endsection
