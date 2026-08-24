@extends('admin.layouts.app')

@php $editing = $album->exists; @endphp

@section('title', $editing ? __('admin.gallery.edit') : __('admin.gallery.create'))
@section('heading', $editing ? $album->untranslated('title') : __('admin.gallery.create'))
@section('subheading', $editing ? __('admin.gallery.edit') : __('admin.gallery.create_help'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.gallery.update', $album) : route('admin.gallery.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="admin-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.gallery.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="title" :label="__('admin.fields.title')" :model="$album" required class="sm:col-span-2" />

                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$album->slug"
                               :help="__('admin.form.slug_help')" class="sm:col-span-2" />

                <x-admin.translatable name="summary" type="textarea" :rows="3" :label="__('admin.fields.summary')"
                                      :model="$album" :help="__('admin.gallery.summary_help')" class="sm:col-span-2" />

                <x-admin.translatable name="description" type="textarea" :rows="6" :label="__('admin.fields.description')"
                                      :model="$album" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            <x-admin.section :title="__('admin.fields.image')">
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$album->untranslated('image')"
                                     set="cover" :seed="$album->id" group="gallery-album"
                                     :help="__('admin.gallery.cover_help')" />
            </x-admin.section>

            <x-admin.section :title="__('admin.form.visibility')">
                <div class="grid gap-4">
                    <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$album->is_active"
                                    :help="__('admin.form.is_active_help')" />
                    <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$album->is_featured" />
                    <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                                   :value="$album->sort_order ?? 0" :help="__('admin.form.sort_help')" />
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.gallery.index')" />
</form>

@if ($editing)
    <div class="admin-form mt-8">
        @include('admin.gallery.photos', ['album' => $album, 'photos' => $photos])

        <x-admin.danger-zone :action="route('admin.gallery.destroy', $album)"
                            :description="__('admin.gallery.delete_help')" />
    </div>
@endif
@endsection
