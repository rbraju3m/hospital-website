@extends('admin.layouts.app')

@php $editing = $package->exists; @endphp

@section('title', $editing ? __('admin.packages.edit') : __('admin.packages.create'))
@section('heading', $editing ? $package->untranslated('name') : __('admin.packages.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.packages.update', $package) : route('admin.packages.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.packages.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="space-y-6">
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="name" :label="__('admin.fields.name')" :model="$package" required class="sm:col-span-2" />
                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$package->slug" :help="__('admin.form.slug_help')" />

                <x-admin.select name="category" :label="__('admin.fields.category')" required :value="$package->category"
                                :options="collect(\App\Http\Requests\Admin\HealthPackageRequest::CATEGORIES)
                                    ->mapWithKeys(fn ($c) => [$c => category_label('packages', $c)])->all()" />

                <x-admin.translatable name="summary" type="textarea" :rows="3" :label="__('admin.fields.summary')" :model="$package" class="sm:col-span-2" />
                <x-admin.translatable name="description" type="textarea" :rows="6" :label="__('admin.fields.description')" :model="$package"
                                      :help="__('admin.form.markup_help')" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.packages.pricing')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="price" type="number" :label="__('admin.fields.price')" required
                               :value="$package->price ?? 0" :help="__('admin.form.money_help')" />
                <x-admin.input name="discount_price" type="number" :label="__('admin.fields.discount_price')"
                               :value="$package->discount_price" :help="__('admin.packages.discount_help')" />
                <x-admin.translatable name="duration" :label="__('admin.fields.duration')" :model="$package" />
                <x-admin.translatable name="suitable_for" :label="__('admin.fields.suitable_for')" :model="$package" />
                <x-admin.translatable name="tests" type="list" :rows="6" :label="__('admin.fields.tests')" :model="$package" class="sm:col-span-2" />
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$package->untranslated('image')"
                                     set="cover" :seed="$package->id" group="package" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.form.visibility')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$package->is_active"
                                :help="__('admin.form.is_active_help')" />
                <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$package->is_featured" />
                <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                               :value="$package->sort_order ?? 0" :help="__('admin.form.sort_help')" />
            </div>
        </x-admin.section>
    </div>

    <x-admin.form-actions :cancel="route('admin.packages.index')" />
</form>

@if ($editing)
    <div class="max-w-4xl">
        <x-admin.danger-zone :action="route('admin.packages.destroy', $package)" />
    </div>
@endif
@endsection
