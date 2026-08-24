@extends('admin.layouts.app')

@php $editing = $doctor->exists; @endphp

@section('title', $editing ? __('admin.doctors.edit') : __('admin.doctors.create'))
@section('heading', $editing ? $doctor->untranslated('name') : __('admin.doctors.create'))
@section('subheading', $editing ? __('admin.doctors.edit') : __('admin.doctors.create_help'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.doctors.update', $doctor) : route('admin.doctors.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.doctors.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="space-y-6">
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="name" :label="__('admin.fields.name')" :model="$doctor" required
                                      :placeholder="__('admin.doctors.name_placeholder')" class="sm:col-span-2" />

                <x-admin.select name="department_id" :label="__('admin.fields.department_id')" required
                                :value="$doctor->department_id"
                                :placeholder="__('admin.doctors.pick_department')"
                                :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->untranslated('name')])->all()" />

                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$doctor->slug" :help="__('admin.form.slug_help')" />

                <x-admin.translatable name="designation" :label="__('admin.fields.designation')" :model="$doctor" />
                <x-admin.translatable name="speciality" :label="__('admin.fields.speciality')" :model="$doctor" />

                {{-- Not translatable by design: MBBS / FCPS / MRCP stay in Latin
                     script in Bangla usage, so there is only one field. --}}
                <x-admin.input name="qualifications" :label="__('admin.fields.qualifications')"
                               :value="$doctor->qualifications" :help="__('admin.doctors.qualifications_help')"
                               class="sm:col-span-2" />

                <x-admin.select name="gender" :label="__('admin.fields.gender')" required :value="$doctor->gender"
                                :options="['male' => __('admin.gender.male'), 'female' => __('admin.gender.female')]" />

                <x-admin.input name="experience_years" type="number" :label="__('admin.fields.experience_years')"
                               :value="$doctor->experience_years ?? 0" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.doctors.profile')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="expertise" type="list" :label="__('admin.fields.expertise')" :model="$doctor" />
                <x-admin.translatable name="languages" type="list" :label="__('admin.fields.languages')" :model="$doctor" />
                <x-admin.translatable name="chamber" :label="__('admin.fields.chamber')" :model="$doctor" class="sm:col-span-2" />
                <x-admin.translatable name="about" type="textarea" :rows="8" :label="__('admin.fields.about')" :model="$doctor"
                                      :help="__('admin.form.markup_help')" class="sm:col-span-2" />
                <x-admin.image-field name="photo" :label="__('admin.fields.photo')" :value="$doctor->untranslated('photo')"
                                     :preview="doctor_photo($doctor)"
                                     aspect="aspect-square" :help="__('admin.doctors.photo_help')" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.doctors.fees')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="consultation_fee" type="number" :label="__('admin.fields.consultation_fee')" required
                               :value="$doctor->consultation_fee ?? 0" :help="__('admin.form.money_help')" />
                <x-admin.input name="follow_up_fee" type="number" :label="__('admin.fields.follow_up_fee')"
                               :value="$doctor->follow_up_fee" :help="__('admin.form.money_help')" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.form.visibility')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$doctor->is_active"
                                :help="__('admin.form.is_active_help')" />
                <x-admin.toggle name="accepts_online_appointment" :label="__('admin.fields.accepts_online_appointment')"
                                :value="$doctor->accepts_online_appointment"
                                :help="__('admin.doctors.online_help')" />
                <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$doctor->is_featured"
                                :help="__('admin.doctors.featured_help')" />
                <x-admin.input name="sort_order" type="number" :label="__('admin.fields.sort_order')"
                               :value="$doctor->sort_order ?? 0" :help="__('admin.form.sort_help')" />
            </div>
        </x-admin.section>
    </div>

    <x-admin.form-actions :cancel="route('admin.doctors.index')" />
</form>

@if ($editing)
    <div class="mt-8 max-w-4xl">
        @include('admin.doctors.schedules', ['doctor' => $doctor])

        <x-admin.danger-zone :action="route('admin.doctors.destroy', $doctor)"
                             :description="__('admin.doctors.delete_help')" />
    </div>
@endif
@endsection
