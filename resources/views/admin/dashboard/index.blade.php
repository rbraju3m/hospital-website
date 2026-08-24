@extends('admin.layouts.app')

@section('title', __('admin.nav.dashboard'))
@section('heading', __('admin.dashboard.heading', ['name' => Str::before(auth()->user()->name, ' ')]))
@section('subheading', now()->translatedFormat('l, j F Y'))

@section('content')
    @php
        $tiles = [
            ['label' => __('admin.dashboard.today'), 'value' => $stats['today'], 'icon' => 'calendar-check', 'href' => route('admin.appointments.index', ['date' => now()->toDateString()])],
            ['label' => __('admin.dashboard.pending'), 'value' => $stats['pending'], 'icon' => 'clock', 'href' => route('admin.appointments.index', ['status' => 'pending'])],
            ['label' => __('admin.dashboard.next_seven'), 'value' => $stats['week'], 'icon' => 'calendar', 'href' => route('admin.appointments.index')],
            ['label' => __('admin.dashboard.unread'), 'value' => $stats['unread'], 'icon' => 'inbox', 'href' => route('admin.messages.index', ['unread' => 1])],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-reveal-stagger="60">
        @foreach ($tiles as $tile)
            <a href="{{ $tile['href'] }}" class="admin-stat reveal group">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-navy-900/55">{{ $tile['label'] }}</p>
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-mist-100 text-navy-900/50
                                 transition duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-50 group-hover:text-teal-700">
                        <x-icon :name="$tile['icon']" size="17" />
                    </span>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-navy-900" data-countup>{{ number_format($tile['value']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Today's list is the reason a receptionist opens this page at all. --}}
        <section class="admin-card xl:col-span-2">
            <header class="flex items-center justify-between gap-3 border-b border-mist-200 px-5 py-4">
                <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.todays_schedule') }}</h2>
                <a href="{{ route('admin.appointments.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    {{ __('admin.actions.view_all') }} →
                </a>
            </header>

            @forelse ($todaysAppointments as $appointment)
                <a href="{{ route('admin.appointments.show', $appointment) }}"
                   class="flex items-center gap-4 border-b border-mist-100 px-5 py-3.5 transition duration-150 last:border-0
                          hover:bg-mist-50 hover:ps-6">
                    <span class="w-20 shrink-0 font-display text-sm font-bold text-navy-900">
                        {{ $appointment->formattedTime() }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-navy-900">{{ $appointment->patient_name }}</span>
                        <span class="block truncate text-xs text-navy-900/50">{{ $appointment->doctor?->name }}</span>
                    </span>
                    <x-admin.status-badge :status="$appointment->status" />
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-navy-900/45">{{ __('admin.dashboard.no_appointments_today') }}</p>
            @endforelse
        </section>

        <div class="space-y-6">
            <section class="admin-card">
                <header class="flex items-center justify-between gap-3 border-b border-mist-200 px-5 py-4">
                    <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.recent_messages') }}</h2>
                    <a href="{{ route('admin.messages.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                        {{ __('admin.actions.view_all') }} →
                    </a>
                </header>

                @forelse ($recentMessages as $message)
                    <a href="{{ route('admin.messages.show', $message) }}"
                       class="block border-b border-mist-100 px-5 py-3.5 transition duration-150 last:border-0
                              hover:bg-mist-50 hover:ps-6">
                        <span class="flex items-center gap-2">
                            @unless ($message->is_read)
                                <span class="pulse-dot shrink-0 text-teal-500"></span>
                            @endunless
                            <span class="truncate text-sm font-semibold text-navy-900">{{ $message->name }}</span>
                            <span class="ms-auto shrink-0 text-[11px] text-navy-900/40">{{ $message->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="mt-1 block truncate text-xs text-navy-900/55">{{ $message->subject ?: $message->message }}</span>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-navy-900/45">{{ __('admin.dashboard.no_messages') }}</p>
                @endforelse
            </section>

            <section class="admin-card p-5">
                <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.catalogue') }}</h2>
                <dl class="mt-4 space-y-2.5">
                    @foreach ($catalogue as $key => $count)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-navy-900/55">{{ __("admin.nav.{$key}") }}</dt>
                            <dd class="font-semibold text-navy-900">{{ number_format($count) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- Untranslated content silently falls back to English on a Bangla
                 page, so it needs surfacing rather than discovering. --}}
            @if ($translationGaps)
                <section class="admin-card p-5">
                    <div class="flex items-center gap-2">
                        <x-icon name="languages" size="17" class="text-amber-600" />
                        <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.translation_gaps') }}</h2>
                    </div>

                    @foreach ($translationGaps as $locale => $counts)
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-navy-900/40">
                            {{ config("app.available_locales.{$locale}.native", $locale) }}
                        </p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($counts as $key => $count)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <a href="{{ route("admin.{$key}.index", ['untranslated' => $locale]) }}"
                                       class="text-navy-900/70 hover:text-teal-700">{{ __("admin.nav.{$key}") }}</a>
                                    <span class="badge-amber">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </section>
            @endif
        </div>
    </div>
@endsection
