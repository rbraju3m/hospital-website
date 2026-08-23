@extends('portal.layouts.app')

@section('title', __('portal.nav.dashboard'))

@section('content')
    <h1 class="font-display text-2xl font-bold text-navy-900">
        {{ __('portal.dashboard.greeting', ['name' => Str::before($patient->name, ' ')]) }}
    </h1>
    <p class="mt-1 text-sm text-navy-900/55">{{ $patient->displayPhone() }}</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <section class="card overflow-hidden lg:col-span-2">
            <header class="flex items-center justify-between gap-3 border-b border-mist-200 px-5 py-4">
                <h2 class="font-display text-base font-bold text-navy-900">{{ __('portal.dashboard.upcoming_title') }}</h2>
                <a href="{{ route('portal.appointments') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    {{ __('portal.dashboard.view_all') }} →
                </a>
            </header>

            @forelse ($upcoming as $appointment)
                @include('portal.partials.appointment-card', ['appointment' => $appointment])
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-navy-900/50">{{ __('portal.dashboard.no_upcoming') }}</p>
                    <a href="{{ route('appointment.create') }}" class="btn-accent btn-sm mt-5">
                        <x-icon name="calendar-check" size="15" />
                        {{ __('portal.dashboard.book_cta') }}
                    </a>
                </div>
            @endforelse
        </section>

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <a href="{{ route('portal.appointments') }}" class="card-interactive p-5">
                    <p class="text-sm text-navy-900/55">{{ __('portal.dashboard.stat_appointments') }}</p>
                    <p class="mt-1 font-display text-2xl font-extrabold text-navy-900">{{ number_format($counts['appointments']) }}</p>
                </a>
                <a href="{{ route('portal.documents') }}" class="card-interactive p-5">
                    <p class="text-sm text-navy-900/55">{{ __('portal.dashboard.stat_documents') }}</p>
                    <p class="mt-1 font-display text-2xl font-extrabold text-navy-900">{{ number_format($counts['documents']) }}</p>
                </a>
            </div>
        </div>
    </div>

    <section class="card mt-6 overflow-hidden">
        <header class="flex items-center justify-between gap-3 border-b border-mist-200 px-5 py-4">
            <h2 class="font-display text-base font-bold text-navy-900">{{ __('portal.dashboard.documents_title') }}</h2>
            <a href="{{ route('portal.documents') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                {{ __('portal.dashboard.view_all') }} →
            </a>
        </header>

        @forelse ($recent as $document)
            @include('portal.partials.document-row', ['document' => $document])
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-navy-900/50">{{ __('portal.dashboard.no_documents') }}</p>
                <p class="mt-1.5 text-xs text-navy-900/40">{{ __('portal.dashboard.documents_hint') }}</p>
            </div>
        @endforelse
    </section>
@endsection
