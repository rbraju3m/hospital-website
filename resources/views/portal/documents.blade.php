@extends('portal.layouts.app')

@section('title', __('portal.documents.title'))

@section('content')
    <h1 class="font-display text-2xl font-bold text-navy-900">{{ __('portal.documents.title') }}</h1>
    <p class="mt-1.5 max-w-2xl text-sm text-navy-900/55">{{ __('portal.documents.lede') }}</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('portal.documents') }}"
           @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>
            {{ __('portal.documents.all') }}
            <span class="opacity-60">{{ $counts->sum() }}</span>
        </a>

        @foreach (\App\Models\PatientDocument::CATEGORIES as $slug)
            @continue(($counts[$slug] ?? 0) === 0)
            <a href="{{ route('portal.documents', ['category' => $slug]) }}"
               @class(['btn-sm', $category === $slug ? 'btn-primary' : 'btn-outline'])>
                {{ __("portal.categories.{$slug}") }}
                <span class="opacity-60">{{ $counts[$slug] }}</span>
            </a>
        @endforeach
    </div>

    <section class="card mt-6 overflow-hidden">
        @forelse ($documents as $document)
            @include('portal.partials.document-row', ['document' => $document])
        @empty
            <div class="px-5 py-16 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-navy-900/35">
                    <x-icon name="file-text" size="22" />
                </span>
                <p class="mt-4 text-sm text-navy-900/50">{{ __('portal.documents.none') }}</p>
                <p class="mt-1.5 text-xs text-navy-900/40">{{ __('portal.dashboard.documents_hint') }}</p>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $documents->links() }}</div>
@endsection
