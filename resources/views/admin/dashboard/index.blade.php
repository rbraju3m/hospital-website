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

    {{-- Site status. Nothing here is news on a normal day; the day something
         is switched off it is the first thing anybody needs to know. --}}
    @if ($maintenance || $featuresOff->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-4 rounded-2xl border px-5 py-4
                    {{ $maintenance ? 'border-urgent-100 bg-urgent-50' : 'border-amber-200 bg-amber-50' }}">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl
                         {{ $maintenance ? 'bg-urgent-100 text-urgent-700' : 'bg-amber-100 text-amber-700' }}">
                <x-icon :name="$maintenance ? 'power' : 'eye-off'" size="19" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold {{ $maintenance ? 'text-urgent-700' : 'text-amber-900' }}">
                    {{ $maintenance
                        ? __('admin.dashboard.site_maintenance')
                        : trans_choice('admin.dashboard.site_hiding', $featuresOff->count(), ['count' => $featuresOff->count()]) }}
                </p>
                <p class="mt-0.5 truncate text-xs {{ $maintenance ? 'text-urgent-700/75' : 'text-amber-900/70' }}">
                    {{ $maintenance
                        ? __('admin.dashboard.site_maintenance_hint')
                        : $featuresOff->take(4)->map(fn ($key) => __("admin.site_controls.keys.{$key}"))->implode(' · ') }}
                </p>
            </div>

            <a href="{{ route('admin.site.edit') }}" class="btn-outline btn-sm shrink-0">
                <x-icon name="sliders" size="15" />
                {{ __('admin.nav.site_controls') }}
            </a>
        </div>
    @endif

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

    {{-- The week ahead. A column per day, scaled against the busiest one, so a
         quiet Friday and a full Sunday are visible without reading a number. --}}
    @php $peak = max(1, collect($weekTrend)->max('count')); @endphp

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="admin-card reveal p-5 xl:col-span-2">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.week_ahead') }}</h2>
                <a href="{{ route('admin.appointments.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    {{ __('admin.actions.view_all') }} →
                </a>
            </header>

            <div class="mt-6 flex h-44 items-stretch gap-2 sm:gap-3">
                @foreach ($weekTrend as $index => $day)
                    <a href="{{ route('admin.appointments.index', ['date' => $day['date']->toDateString()]) }}"
                       class="group flex flex-1 flex-col items-center gap-2"
                       title="{{ $day['date']->translatedFormat('l, j F') }}">
                        <span class="text-xs font-semibold text-navy-900/70 transition group-hover:text-teal-700">
                            {{ $day['count'] }}
                        </span>

                        {{-- The track takes the leftover height so every column
                             shares one scale; the fill is positioned inside it,
                             which is what makes a percentage height definite. --}}
                        <span class="relative w-full min-h-0 flex-1 overflow-hidden rounded-t-lg bg-mist-100
                                     transition-colors duration-200 group-hover:bg-mist-200">
                            {{-- A floor of 4% so an empty day is still a target
                                 rather than a hairline nobody can hit. --}}
                            <span class="absolute inset-x-0 bottom-0 origin-bottom rounded-t-lg
                                         {{ $day['date']->isToday() ? 'bg-gradient-to-t from-teal-700 to-teal-400' : 'bg-gradient-to-t from-navy-800 dark:from-navy-200 to-navy-400' }}"
                                  style="height: {{ max(4, (int) round($day['count'] / $peak * 100)) }}%;
                                         animation: bar-rise var(--duration-slow) var(--ease-out-expo) both;
                                         animation-delay: {{ $index * 60 }}ms"></span>
                        </span>

                        <span class="text-[11px] font-medium {{ $day['date']->isToday() ? 'text-teal-700' : 'text-navy-900/45' }}">
                            {{ $day['date']->translatedFormat('D') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="admin-card reveal p-5">
            <h2 class="font-display text-base font-bold text-navy-900">{{ __('admin.dashboard.status_breakdown') }}</h2>
            <p class="mt-0.5 text-xs text-navy-900/50">{{ __('admin.dashboard.status_breakdown_hint') }}</p>

            @php $statusTotal = max(1, array_sum($statusBreakdown)); @endphp

            <dl class="mt-5 space-y-4">
                @foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $index => $status)
                    @php $count = $statusBreakdown[$status] ?? 0; @endphp
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="flex items-center gap-2 text-navy-900/65">
                                <x-admin.status-badge :status="$status" />
                            </dt>
                            <dd class="font-semibold text-navy-900">{{ number_format($count) }}</dd>
                        </div>
                        <div class="meter mt-2" style="--anim-delay: {{ $index * 90 }}ms">
                            <span style="width: {{ round($count / $statusTotal * 100) }}%"></span>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>
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
