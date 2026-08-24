@extends('admin.layouts.app')

@php $editing = $service->exists; @endphp

@section('title', $editing ? __('admin.services.edit') : __('admin.services.create'))
@section('heading', $editing ? $service->untranslated('name') : __('admin.services.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.services.update', $service) : route('admin.services.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.services.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="space-y-6">
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="name" :label="__('admin.fields.name')" :model="$service" required class="sm:col-span-2" />
                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$service->slug" :help="__('admin.form.slug_help')" />

                <x-admin.select name="category" :label="__('admin.fields.category')" required :value="$service->category"
                                :options="collect(\App\Http\Requests\Admin\ServiceRequest::CATEGORIES)
                                    ->mapWithKeys(fn ($c) => [$c => __('services.groups.'.$c)])->all()" />

                <x-admin.select name="icon" :label="__('admin.fields.icon')" :value="$service->icon" required
                                :options="collect(config('icons'))->flatten()->mapWithKeys(fn ($i) => [$i => $i])->all()" />

                <x-admin.translatable name="summary" type="textarea" :rows="3" :label="__('admin.fields.summary')" :model="$service" class="sm:col-span-2" />
                <x-admin.translatable name="description" type="textarea" :rows="8" :label="__('admin.fields.description')" :model="$service"
                                      :help="__('admin.form.markup_help')" class="sm:col-span-2" />
                <x-admin.translatable name="highlights" type="list" :label="__('admin.fields.highlights')" :model="$service" />
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$service->untranslated('image')"
                                     set="cover" :seed="$service->id" group="service" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.form.visibility')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$service->is_active"
                                :help="__('admin.form.is_active_help')" />
                <x-admin.toggle name="is_247" :label="__('admin.fields.is_247')" :value="$service->is_247"
                                :help="__('admin.services.is_247_help')" />
                <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$service->is_featured" />
                <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                               :value="$service->sort_order ?? 0" :help="__('admin.form.sort_help')" />
            </div>
        </x-admin.section>
    </div>

    <x-admin.form-actions :cancel="route('admin.services.index')" />
</form>

@if ($editing)
    <div class="max-w-4xl">
        <x-admin.danger-zone :action="route('admin.services.destroy', $service)" />
    </div>
@endif
@endsection
