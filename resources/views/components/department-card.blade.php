@props(['department'])

@php
    $cover = image_url($department->untranslated('image'), 'cover', $department->id, 'department');
@endphp

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col overflow-hidden']) }}>

    @if ($cover)
        <div class="relative aspect-[16/10] overflow-hidden bg-mist-100">
            <img src="{{ $cover }}" alt="" loading="lazy" data-fade
                 class="card-zoom h-full w-full object-cover">
            <div class="media-scrim"></div>
        </div>
    @else
        <span class="mt-7 ms-7 grid h-12 w-12 place-items-center rounded-xl bg-teal-50 text-teal-700 transition
                     duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white">
            <x-icon :name="$department->icon" size="24" />
        </span>
    @endif

    <div class="flex flex-1 flex-col p-6">
        @if ($cover)
            {{-- The icon tile is pulled up over the photograph's lower edge,
                 which ties the two halves of the card together without a rule.
                 Pulled rather than absolutely placed, so it cannot be clipped
                 by the image's own overflow. --}}
            <span class="-mt-12 mb-4 grid h-12 w-12 place-items-center rounded-xl bg-white dark:bg-navy-100 text-teal-700
                         shadow-lift transition duration-300 ease-out
                         group-hover:-translate-y-1 group-hover:bg-teal-600 group-hover:text-white">
                <x-icon :name="$department->icon" size="23" />
            </span>
        @endif

        <h3 class="font-display text-lg font-bold leading-snug text-navy-900">
            <a href="{{ route('departments.show', $department) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                {{ $department->name }}
            </a>
        </h3>

        <p class="mt-2 text-sm leading-relaxed text-navy-900/60">{{ $department->tagline }}</p>

        @if ($department->doctors_count ?? false)
            <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-navy-900/45">
                <x-icon name="stethoscope" size="14" class="text-teal-600" />
                {{ __('common.consultants_count', ['count' => $department->doctors_count]) }}
            </p>
        @endif

        <span class="card-arrow mt-auto pt-5 text-sm font-semibold text-teal-700">
            {{ __('common.explore_department') }} →
        </span>
    </div>
</article>
