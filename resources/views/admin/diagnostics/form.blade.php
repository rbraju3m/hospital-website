@extends('admin.layouts.app')

@php $editing = $test->exists; @endphp

@section('title', $editing ? __('admin.diagnostics.edit') : __('admin.diagnostics.create'))
@section('heading', $editing ? $test->untranslated('name') : __('admin.diagnostics.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.diagnostics.update', $test) : route('admin.diagnostics.store') }}"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="admin-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.diagnostics.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="name" :label="__('admin.fields.name')" :model="$test" required class="sm:col-span-2" />

                <x-admin.select name="category" :label="__('admin.fields.category')" required :value="$test->category"
                                :options="collect(\App\Http\Controllers\Web\DiagnosticController::CATEGORIES)
                                    ->mapWithKeys(fn ($c) => [$c => category_label('diagnostics', $c)])->all()" />

                {{-- Not translatable: an order code is an identifier the counter
                     reads back, like a doctor's post-nominals. --}}
                <x-admin.input name="code" :label="__('admin.fields.code')" :value="$test->code"
                               :help="__('admin.diagnostics.code_help')" />

                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$test->slug"
                               :help="__('admin.form.slug_help')" class="sm:col-span-2" />

                <x-admin.translatable name="summary" type="textarea" :rows="3" :label="__('admin.fields.summary')"
                                      :model="$test" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.diagnostics.pricing')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="price" type="number" :label="__('admin.fields.price')" required
                               :value="$test->price ?? 0" :help="__('admin.form.money_help')" />
                <x-admin.input name="discount_price" type="number" :label="__('admin.fields.discount_price')"
                               :value="$test->discount_price" :help="__('admin.diagnostics.discount_help')" />

                <x-admin.translatable name="preparation" type="textarea" :rows="4" :label="__('admin.fields.preparation')"
                                      :model="$test" class="sm:col-span-2" />
                <x-admin.translatable name="sample_type" :label="__('admin.fields.sample_type')" :model="$test" />
                <x-admin.translatable name="report_time" :label="__('admin.fields.report_time')" :model="$test" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            <x-admin.section :title="__('admin.form.visibility')">
                <div class="grid gap-4">
                    <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$test->is_active"
                                    :help="__('admin.form.is_active_help')" />
                    <x-admin.toggle name="is_home_collection" :label="__('admin.fields.is_home_collection')"
                                    :value="$test->is_home_collection" :help="__('admin.diagnostics.home_collection_help')" />
                    <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$test->is_featured" />
                    <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                                   :value="$test->sort_order ?? 0" :help="__('admin.form.sort_help')" />
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.diagnostics.index')" />
</form>

@if ($editing)
    <div class="admin-form">
        <x-admin.danger-zone :action="route('admin.diagnostics.destroy', $test)" />
    </div>
@endif
@endsection
