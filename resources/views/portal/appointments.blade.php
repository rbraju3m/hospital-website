@extends('portal.layouts.app')

@section('title', __('portal.appointments.title'))

@section('content')
    <h1 class="font-display text-2xl font-bold text-navy-900">{{ __('portal.appointments.title') }}</h1>

    <section class="card mt-8 overflow-hidden">
        <header class="border-b border-mist-200 px-5 py-4">
            <h2 class="font-display text-base font-bold text-navy-900">{{ __('portal.appointments.upcoming_title') }}</h2>
        </header>

        @forelse ($upcoming as $appointment)
            @include('portal.partials.appointment-card', ['appointment' => $appointment])
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-navy-900/50">{{ __('portal.appointments.none') }}</p>
                <a href="{{ route('appointment.create') }}" class="btn-accent btn-sm mt-5">
                    <x-icon name="calendar-check" size="15" />
                    {{ __('portal.dashboard.book_cta') }}
                </a>
            </div>
        @endforelse
    </section>

    {{-- What a patient may do about a booking, which depends on a switch:
         with changes off the portal shows records and points at the desk. --}}
    <p class="mt-4 text-xs text-navy-900/45">
        {{ feature('behaviour_portal_changes')
            ? __('portal.appointments.change_note_on', ['phone' => setting('appointment_number', setting('hotline'))])
            : __('portal.appointments.change_note_off', ['phone' => setting('appointment_number', setting('hotline'))]) }}
    </p>

    <section class="card mt-8 overflow-hidden">
        <header class="border-b border-mist-200 px-5 py-4">
            <h2 class="font-display text-base font-bold text-navy-900">{{ __('portal.appointments.past_title') }}</h2>
        </header>

        @forelse ($past as $appointment)
            @include('portal.partials.appointment-card', ['appointment' => $appointment])
        @empty
            <p class="px-5 py-12 text-center text-sm text-navy-900/50">{{ __('portal.appointments.none_past') }}</p>
        @endforelse
    </section>

    <div class="mt-6">{{ $past->links() }}</div>
@endsection
