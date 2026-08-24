@props(['doctor'])

@php
    $photo = doctor_photo($doctor);
@endphp

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col overflow-hidden']) }}>

    @if ($photo)
        {{-- Portrait first. A consultant directory is a page of people, and a
             face is what a patient actually scans for. --}}
        <div class="relative aspect-[4/5] overflow-hidden bg-mist-100">
            <img src="{{ $photo }}" alt="{{ $doctor->name }}" loading="lazy" data-fade
                 class="card-zoom h-full w-full object-cover object-top">

            {{-- Capped rather than wrapped: a long department name has to give
                 way to the booking badge, not grow under it. --}}
            <span class="media-badge start-3 top-3 max-w-[58%]">
                <x-icon name="building" size="13" />
                <span class="truncate">{{ $doctor->department->name }}</span>
            </span>

            @if ($doctor->accepts_online_appointment)
                <span class="media-badge end-3 top-3 whitespace-nowrap bg-teal-600/85 text-white">
                    <span class="pulse-dot text-teal-200" aria-hidden="true"></span>
                    {{ __('common.book_now') }}
                </span>
            @endif
        </div>
    @endif

    <div class="flex flex-1 flex-col p-5">
        @unless ($photo)
            <div class="mb-4 flex items-start gap-4">
                <x-doctor-avatar :doctor="$doctor" size="md" />
                <span class="chip mt-1">
                    <x-icon name="building" size="13" />
                    {{ $doctor->department->name }}
                </span>
            </div>
        @endunless

        <h3 class="font-display text-base font-bold leading-snug text-navy-900">
            <a href="{{ route('doctors.show', $doctor) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                {{ $doctor->name }}
            </a>
        </h3>

        <p class="mt-1 text-sm font-medium text-teal-700">{{ $doctor->speciality }}</p>
        <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-navy-900/55">{{ $doctor->qualifications }}</p>

        <div class="mt-3 flex flex-wrap gap-1.5">
            <span class="chip">
                <x-icon name="award" size="13" />
                {{ __('common.years_short', ['count' => $doctor->experience_years]) }}
            </span>
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 border-t border-mist-200 pt-4">
            <div class="text-sm">
                <span class="font-display font-bold text-navy-900">৳{{ number_format($doctor->consultation_fee) }}</span>
                <span class="text-xs text-navy-900/50">{{ __('common.consultation') }}</span>
            </div>

            @if ($doctor->accepts_online_appointment)
                <span class="card-arrow relative z-10 text-sm font-semibold text-teal-700">
                    {{ __('common.book_now') }} →
                </span>
            @else
                <span class="chip">{{ __('common.call_to_book') }}</span>
            @endif
        </div>
    </div>
</article>
