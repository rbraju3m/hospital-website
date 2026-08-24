@extends('admin.layouts.app')

@php $editing = $department->exists; @endphp

@section('title', $editing ? __('admin.departments.edit') : __('admin.departments.create'))
@section('heading', $editing ? $department->untranslated('name') : __('admin.departments.create'))
@section('subheading', $editing ? __('admin.departments.edit') : __('admin.departments.create_help'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.departments.update', $department) : route('admin.departments.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.departments.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="space-y-6">
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="name" :label="__('admin.fields.name')" :model="$department" required class="sm:col-span-2" />
                <x-admin.translatable name="tagline" :label="__('admin.fields.tagline')" :model="$department" :help="__('admin.departments.tagline_help')" class="sm:col-span-2" />

                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$department->slug"
                               :help="__('admin.form.slug_help')" />

                <x-admin.select name="icon" :label="__('admin.fields.icon')" :value="$department->icon" required
                                :options="collect(config('icons'))->flatten()->mapWithKeys(fn ($i) => [$i => $i])->all()" />

                <x-admin.translatable name="summary" type="textarea" :rows="3" :label="__('admin.fields.summary')" :model="$department" class="sm:col-span-2" />
                <x-admin.translatable name="description" type="textarea" :rows="8" :label="__('admin.fields.description')" :model="$department"
                                      :help="__('admin.form.markup_help')" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.departments.detail')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="highlights" type="list" :label="__('admin.fields.highlights')" :model="$department" />
                <x-admin.translatable name="treatments" type="list" :label="__('admin.fields.treatments')" :model="$department" />
                <x-admin.input name="phone" :label="__('admin.fields.phone')" :value="$department->untranslated('phone')" />
                <x-admin.translatable name="location" :label="__('admin.fields.location')" :model="$department" />
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$department->untranslated('image')"
                                     set="cover" :seed="$department->id" group="department" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.form.visibility')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$department->is_active"
                                :help="__('admin.form.is_active_help')" />
                <x-admin.toggle name="is_centre_of_excellence" :label="__('admin.fields.is_centre_of_excellence')"
                                :value="$department->is_centre_of_excellence"
                                :help="__('admin.departments.coe_help')" />
                <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                               :value="$department->sort_order ?? 0" :help="__('admin.form.sort_help')" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.form.seo')">
            <div class="grid gap-5">
                <x-admin.translatable name="meta_title" :label="__('admin.fields.meta_title')" :model="$department" />
                <x-admin.translatable name="meta_description" type="textarea" :rows="2" :label="__('admin.fields.meta_description')" :model="$department" />
            </div>
        </x-admin.section>
    </div>

    <x-admin.form-actions :cancel="route('admin.departments.index')" />
</form>

@if ($editing)
    <div class="max-w-4xl">
        <x-admin.danger-zone :action="route('admin.departments.destroy', $department)"
                             :description="__('admin.departments.delete_help')" />
    </div>
@endif
@endsection
