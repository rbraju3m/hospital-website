@props(['doctor'])

<article {{ $attributes->merge(['class' => 'card-interactive group flex h-full flex-col p-6']) }}>
    <div class="flex items-start gap-4">
        <x-doctor-avatar :doctor="$doctor" size="md" />

        <div class="min-w-0 flex-1">
            <h3 class="font-display text-base font-bold leading-snug text-navy-900">
                <a href="{{ route('doctors.show', $doctor) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                    {{ $doctor->name }}
                </a>
            </h3>
            <p class="mt-1 text-sm font-medium text-teal-700">{{ $doctor->speciality }}</p>
            <p class="mt-1.5 text-xs leading-relaxed text-navy-900/55">{{ $doctor->qualifications }}</p>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-1.5">
        <span class="chip">
            <x-icon name="building" size="13" />
            {{ $doctor->department->name }}
        </span>
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
</article>
