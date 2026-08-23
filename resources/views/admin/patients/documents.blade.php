@extends('admin.layouts.app')

@section('title', __('admin.patients.for_patient', ['name' => $patient->name]))
@section('heading', __('admin.patients.for_patient', ['name' => $patient->name]))
@section('subheading', $patient->displayPhone())

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.patients.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>

        <a href="{{ route('admin.documents.create', ['phone' => $patient->displayPhone()]) }}" class="btn-primary btn-sm">
            <x-icon name="plus" size="15" stroke="2.5" />
            {{ __('admin.documents.create') }}
        </a>
    </div>

    <div class="admin-card max-w-3xl overflow-hidden">
        @if ($documents->isEmpty())
            <x-admin.empty :message="__('admin.documents.empty')" icon="file-text" />
        @else
            <ul>
                @foreach ($documents as $document)
                    <li class="admin-row flex flex-wrap items-center gap-4 px-5 py-4 first:border-0">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-navy-900">{{ $document->title }}</span>
                            <span class="block truncate text-xs text-navy-900/45">
                                {{ __("portal.categories.{$document->category}") }}
                                <span class="mx-1">·</span>{{ $document->readableSize() }}
                                @if ($document->issued_at)
                                    <span class="mx-1">·</span>{{ $document->issued_at->translatedFormat('j M Y') }}
                                @endif
                            </span>
                        </span>

                        <a href="{{ route('admin.documents.download', $document) }}" class="btn-outline btn-sm">
                            <x-icon name="download" size="15" />
                            {{ __('portal.documents.download') }}
                        </a>
                        <a href="{{ route('admin.documents.edit', $document) }}" class="btn-ghost btn-sm">
                            {{ __('admin.actions.edit') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
