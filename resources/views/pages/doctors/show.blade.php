@extends('layouts.site')

@section('title', $doctor->name . ' — ' . $doctor->speciality)
@section('meta_description', __('doctors.show.meta_description', [
    'name' => $doctor->name,
    'designation' => $doctor->designation,
    'hospital' => setting('site_name'),
    'qualifications' => $doctor->qualifications,
    'years' => $doctor->experience_years,
]))

@section('content')

<section class="relative overflow-hidden bg-navy-900 dark:bg-navy-100 text-white">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.12]"
         style="background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);background-size:64px 64px"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-teal-500/20 blur-3xl"></div>

    <div class="shell relative py-14 sm:py-20">
        <nav aria-label="{{ __('common.breadcrumb') }}" class="mb-8">
            <ol class="flex flex-wrap items-center gap-2 text-sm text-white/55">
                <li><a href="{{ route('home') }}" class="transition hover:text-white">{{ __('common.home') }}</a></li>
                <li aria-hidden="true"><x-icon name="chevron-right" size="14" /></li>
                <li><a href="{{ route('doctors.index') }}" class="transition hover:text-white">{{ __('doctors.show.crumb') }}</a></li>
                <li aria-hidden="true"><x-icon name="chevron-right" size="14" /></li>
                <li class="text-white/90">{{ $doctor->name }}</li>
            </ol>
        </nav>

        <div class="flex flex-col gap-8 sm:flex-row sm:items-start">
            <x-doctor-avatar :doctor="$doctor" size="xl" class="ring-4 ring-white/10" />

            <div class="min-w-0 flex-1">
                <p class="eyebrow text-teal-300">
                    <span class="h-px w-6 bg-teal-400"></span>
                    <a href="{{ route('departments.show', $doctor->department) }}" class="hover:text-white">
                        {{ $doctor->department->name }}
                    </a>
                </p>

                <h1 class="mt-3 font-display text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ $doctor->name }}
                </h1>

                <p class="mt-3 text-lg text-teal-300">{{ $doctor->designation }}</p>
                <p class="mt-2 text-sm text-white/60">{{ $doctor->qualifications }}</p>

                <div class="mt-7 flex flex-wrap gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm">
                        <x-icon name="award" size="15" class="text-teal-300" />
                        {{ __('doctors.show.experience', ['count' => $doctor->experience_years]) }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm">
                        <x-icon name="credit-card" size="15" class="text-teal-300" />
                        {{ __('doctors.show.fee', ['amount' => number_format($doctor->consultation_fee)]) }}
                    </span>
                    @if ($doctor->chamber)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm">
                            <x-icon name="map-pin" size="15" class="text-teal-300" />
                            {{ $doctor->chamber }}
                        </span>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($doctor->accepts_online_appointment)
                        <a href="{{ route('appointment.create', ['doctor' => $doctor->slug]) }}" class="btn-accent btn-lg">
                            <x-icon name="calendar-check" size="18" /> {{ __('common.book_appointment') }}
                        </a>
                    @else
                        <span class="btn btn-lg cursor-default border border-white/25 text-white/70">
                            {{ __('doctors.show.booking_unavailable') }}
                        </span>
                    @endif
                    <a href="tel:{{ setting('appointment_number') }}"
                       class="btn btn-lg border border-white/25 text-white hover:bg-white/10">
                        <x-icon name="phone" size="18" /> {{ __('common.call_to_book') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">

        <div class="lg:col-span-8 space-y-14">
            <div>
                <h2 class="h-section">
                    {{ __('doctors.show.about_title', ['name' => str($doctor->name)->after('Dr. ')->before(' ') ?: $doctor->name]) }}
                </h2>
                <x-article-body :body="$doctor->about" class="mt-6" />
            </div>

            @if ($doctor->expertise)
                <div>
                    <h2 class="font-display text-xl font-bold text-navy-900">{{ __('doctors.show.expertise_title') }}</h2>
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($doctor->expertise as $item)
                            <li class="flex items-start gap-3 rounded-xl border border-mist-200 bg-white dark:bg-navy-100 p-4">
                                <x-icon name="check" size="16" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                                <span class="text-sm text-navy-900/75">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($doctor->schedules->isNotEmpty())
                <div>
                    <h2 class="font-display text-xl font-bold text-navy-900">{{ __('doctors.show.schedule_title') }}</h2>
                    <p class="mt-2 text-sm text-navy-900/55">{{ __('doctors.show.schedule_note') }}</p>

                    <div class="mt-6 overflow-hidden rounded-[1.25rem] border border-mist-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-mist-50 text-xs uppercase tracking-wide text-navy-900/55">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">{{ __('doctors.show.schedule_day') }}</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">{{ __('doctors.show.schedule_time') }}</th>
                                    <th scope="col" class="hidden px-5 py-3.5 font-semibold sm:table-cell">{{ __('doctors.show.schedule_location') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-200 bg-white dark:bg-navy-100">
                                @foreach ($doctor->schedules->groupBy('day_of_week') as $day => $blocks)
                                    <tr>
                                        <th scope="row" class="px-5 py-3.5 font-semibold text-navy-900">
                                            {{ \App\Models\DoctorSchedule::dayLabel($day) }}
                                        </th>
                                        <td class="px-5 py-3.5 text-navy-900/70">
                                            {{ $blocks->map->timeRange()->implode(' · ') }}
                                        </td>
                                        <td class="hidden px-5 py-3.5 text-navy-900/55 sm:table-cell">
                                            {{ $blocks->first()->location }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Booking sidebar --}}
        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">{{ __('doctors.show.next_available') }}</h2>

                    @if (! $doctor->accepts_online_appointment)
                        <p class="mt-3 text-sm text-navy-900/60">{{ __('doctors.show.no_online_booking') }}</p>
                        <a href="tel:{{ setting('appointment_number') }}" class="btn-primary mt-5 w-full">
                            <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                        </a>
                    @elseif ($availableDates->isEmpty())
                        <p class="mt-3 text-sm text-navy-900/60">{{ __('doctors.show.no_slots') }}</p>
                        <a href="tel:{{ setting('appointment_number') }}" class="btn-outline mt-5 w-full">
                            <x-icon name="phone" size="16" /> {{ __('doctors.show.call_desk') }}
                        </a>
                    @else
                        <div class="mt-5 space-y-2">
                            @foreach ($availableDates->take(4) as $day)
                                <a href="{{ route('appointment.create', ['doctor' => $doctor->slug, 'date' => $day['date']]) }}"
                                   class="flex items-center justify-between rounded-xl border border-mist-200 px-4 py-3
                                          transition hover:border-teal-300 hover:bg-teal-50">
                                    <span>
                                        <span class="block text-sm font-semibold text-navy-900">
                                            {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('l, j F') }}
                                        </span>
                                        <span class="block text-xs text-navy-900/50">
                                            {{ trans_choice('doctors.show.slots_open', $day['slots'], ['count' => $day['slots']]) }}
                                        </span>
                                    </span>
                                    <x-icon name="chevron-right" size="18" class="text-teal-600" />
                                </a>
                            @endforeach
                        </div>

                        <a href="{{ route('appointment.create', ['doctor' => $doctor->slug]) }}" class="btn-accent mt-5 w-full">
                            <x-icon name="calendar-check" size="16" /> {{ __('doctors.show.see_all_dates') }}
                        </a>
                    @endif

                    <dl class="mt-7 space-y-3 border-t border-mist-200 pt-6 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-navy-900/55">{{ __('doctors.show.fee_new') }}</dt>
                            <dd class="font-display font-bold text-navy-900">৳{{ number_format($doctor->consultation_fee) }}</dd>
                        </div>
                        @if ($doctor->follow_up_fee)
                            <div class="flex items-center justify-between">
                                <dt class="text-navy-900/55">{{ __('doctors.show.fee_follow_up') }}</dt>
                                <dd class="font-display font-bold text-navy-900">৳{{ number_format($doctor->follow_up_fee) }}</dd>
                            </div>
                        @endif
                        @if ($doctor->languages)
                            <div class="flex items-center justify-between">
                                <dt class="text-navy-900/55">{{ __('doctors.show.languages') }}</dt>
                                <dd class="font-medium text-navy-900">{{ implode(', ', $doctor->languages) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </aside>
    </div>
</section>

@if ($colleagues->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading
                :eyebrow="__('doctors.show.colleagues_eyebrow')"
                :title="__('doctors.show.colleagues_title', ['department' => $doctor->department->name])"
                :link="route('departments.show', $doctor->department)"
                :link-label="__('doctors.show.colleagues_link')"
                class="reveal" />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($colleagues as $colleague)
                    <x-doctor-card :doctor="$colleague" class="reveal" />
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
