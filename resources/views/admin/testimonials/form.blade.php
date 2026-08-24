@extends('admin.layouts.app')

@php $editing = $testimonial->exists; @endphp

@section('title', $editing ? __('admin.testimonials.edit') : __('admin.testimonials.create'))
@section('heading', $editing ? $testimonial->untranslated('patient_name') : __('admin.testimonials.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="admin-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.testimonials.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="patient_name" :label="__('admin.fields.patient_name')" :model="$testimonial" required />
                <x-admin.translatable name="location" :label="__('admin.fields.location')" :model="$testimonial" />
                <x-admin.translatable name="treatment" :label="__('admin.fields.treatment')" :model="$testimonial" />

                <x-admin.select name="rating" :label="__('admin.fields.rating')" required :value="$testimonial->rating"
                                :options="collect(range(5, 1))->mapWithKeys(fn ($r) => [$r => trans_choice('admin.testimonials.stars', $r, ['count' => $r])])->all()" />

                <x-admin.translatable name="quote" type="textarea" :rows="6" :label="__('admin.fields.quote')" :model="$testimonial"
                                      required class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            {{-- A testimonial gets no stand-in face on the public site, so this
                 preview is either the real photograph or nothing at all. --}}
            <x-admin.section :title="__('admin.fields.photo')">
                <x-admin.image-field name="photo" :label="__('admin.fields.photo')" :value="$testimonial->untranslated('photo')"
                                     aspect="aspect-square" />
            </x-admin.section>

            <x-admin.section :title="__('admin.form.visibility')">
                <div class="grid gap-4">
                    <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$testimonial->is_active"
                                    :help="__('admin.form.is_active_help')" />
                    <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                                   :value="$testimonial->sort_order ?? 0" :help="__('admin.form.sort_help')" />
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.testimonials.index')" />
</form>

@if ($editing)
    <div class="admin-form">
        <x-admin.danger-zone :action="route('admin.testimonials.destroy', $testimonial)" />
    </div>
@endif
@endsection
