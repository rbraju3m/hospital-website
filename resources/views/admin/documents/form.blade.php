@extends('admin.layouts.app')

@php $editing = $document->exists; @endphp

@section('title', $editing ? __('admin.documents.edit') : __('admin.documents.create'))
@section('heading', $editing ? $document->title : __('admin.documents.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.documents.update', $document) : route('admin.documents.store') }}"
      enctype="multipart/form-data" class="admin-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5">
        <a href="{{ route('admin.documents.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.form-layout>
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="phone" :label="__('admin.fields.phone')" required
                               :value="$document->exists ? '0'.$document->phone : $document->phone"
                               placeholder="01XXXXXXXXX" :help="__('admin.documents.phone_help')" class="sm:col-span-2" />

                <x-admin.input name="title" :label="__('admin.fields.title')" required :value="$document->title"
                               class="sm:col-span-2" />

                <x-admin.select name="category" :label="__('admin.fields.category')" required :value="$document->category"
                                :options="collect(\App\Models\PatientDocument::CATEGORIES)
                                    ->mapWithKeys(fn ($c) => [$c => __(\"portal.categories.{$c}\")])->all()" />

                <x-admin.input name="issued_at" type="date" :label="__('admin.fields.issued_at')"
                               :value="$document->issued_at?->toDateString()" />

                <x-admin.textarea name="notes" :rows="3" :label="__('admin.fields.notes')" :value="$document->notes"
                                  class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-slot:aside>
            <x-admin.section :title="__('admin.fields.file')">
                <div>
                    <label for="file" class="label">
                        {{ __('admin.fields.file') }}
                        @unless ($editing)
                            <span class="text-urgent-600" aria-hidden="true">*</span>
                        @endunless
                    </label>

                    @if ($editing)
                        <p class="mb-2 flex flex-wrap items-center gap-2 text-sm text-navy-900/70">
                            <x-icon name="file-text" size="16" class="text-teal-600" />
                            {{ __('admin.documents.current_file') }}:
                            <a href="{{ route('admin.documents.download', $document) }}"
                               class="font-medium text-teal-700 hover:underline">{{ $document->original_name }}</a>
                            <span class="text-xs text-navy-900/45">({{ $document->readableSize() }})</span>
                        </p>
                    @endif

                    <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png"
                           class="block w-full text-sm text-navy-900/70
                                  file:me-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-4 file:py-2
                                  file:text-sm file:font-semibold file:text-white hover:file:bg-navy-800">

                    <p class="mt-1.5 text-xs text-navy-900/45">
                        {{ $editing
                            ? __('admin.documents.replace_help')
                            : __('admin.documents.file_help', ['size' => (int) (\App\Http\Requests\Admin\PatientDocumentRequest::MAX_KILOBYTES / 1024)]) }}
                    </p>

                    @error('file') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </x-admin.section>
        </x-slot:aside>
    </x-admin.form-layout>

    <x-admin.form-actions :cancel="route('admin.documents.index')" />
</form>

@if ($editing)
    <div class="admin-form">
        <x-admin.danger-zone :action="route('admin.documents.destroy', $document)"
                             :description="__('admin.documents.delete_help')" />
    </div>
@endif
@endsection
