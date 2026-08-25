@extends('admin.layouts.app')

@php $editing = $slide->exists; @endphp

@section('title', $editing ? __('admin.slides.edit') : __('admin.slides.create'))
@section('heading', $editing ? $slide->untranslated('title') : __('admin.slides.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.slides.update', $slide) : route('admin.slides.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="admin-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.slides.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.form.basics')" :description="__('admin.slides.words_help')">
            <div class="grid gap-5">
                <x-admin.translatable name="eyebrow" :label="__('admin.fields.eyebrow')" :model="$slide"
                                      :help="__('admin.slides.eyebrow_help')" />
                <x-admin.translatable name="title" :label="__('admin.fields.title')" :model="$slide" required />
                <x-admin.translatable name="subtitle" type="textarea" :rows="3" :label="__('admin.fields.subtitle')"
                                      :model="$slide" />
            </div>
        </x-admin.section>

        {{-- Two buttons at most. A slide a visitor has four seconds to read
             carries one idea and one thing to do about it; the second button is
             for the quieter alternative — "call us instead". --}}
        <x-admin.section :title="__('admin.slides.buttons')" :description="__('admin.slides.buttons_help')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="cta_label" :label="__('admin.fields.cta_label')" :model="$slide" />
                <x-admin.input name="cta_url" :label="__('admin.fields.cta_url')" :value="$slide->cta_url"
                               :help="__('admin.slides.url_help')" placeholder="/appointment" />

                <x-admin.translatable name="cta_secondary_label" :label="__('admin.fields.cta_secondary_label')" :model="$slide" />
                <x-admin.input name="cta_secondary_url" :label="__('admin.fields.cta_secondary_url')"
                               :value="$slide->cta_secondary_url" placeholder="tel:{{ setting('hotline') }}" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            <x-admin.section :title="__('admin.fields.image')">
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$slide->untranslated('image')"
                                     set="hero" :seed="$slide->id" group="slides" aspect="aspect-[16/9]"
                                     :help="__('admin.slides.image_help')" />
            </x-admin.section>

            <x-admin.section :title="__('admin.form.visibility')">
                <div class="grid gap-4">
                    <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$slide->is_active"
                                    :help="__('admin.form.is_active_help')" />
                    <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                                   :value="$slide->sort_order ?? 0" :help="__('admin.form.sort_help')" />
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.slides.index')" />
</form>

@if ($editing)
    <div class="admin-form">
        <x-admin.danger-zone :action="route('admin.slides.destroy', $slide)" />
    </div>
@endif
@endsection
