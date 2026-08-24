@props(['testimonial'])

@php
    /* An uploaded photograph only. There is no honest stand-in for a named
       patient — a face that is not theirs against their name and their words
       is a different claim from a stock ward behind a department heading. */
    $photo = media_url($testimonial->untranslated('photo'));
@endphp

<figure {{ $attributes->merge(['class' => 'card-hover group flex h-full flex-col p-7']) }}>
    <div class="flex items-start justify-between gap-4">
        <x-icon name="quote" size="26" stroke="1.4"
                class="text-teal-200 transition duration-300 ease-out group-hover:scale-110 group-hover:text-teal-300" />
        <x-rating :rating="$testimonial->rating" class="shrink-0" />
    </div>

    <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-navy-900/75">
        “{{ $testimonial->quote }}”
    </blockquote>

    <figcaption class="mt-6 flex items-center gap-3 border-t border-mist-200 pt-5">
        @if ($photo)
            <img src="{{ $photo }}" alt="" loading="lazy" data-fade
                 class="h-11 w-11 shrink-0 rounded-full bg-mist-100 object-cover
                        ring-2 ring-white transition duration-300 ease-out group-hover:ring-teal-200">
        @else
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-navy-900 dark:bg-navy-100 text-xs font-bold text-white">
                {{ str($testimonial->patient_name)->substr(0, 1) }}
            </span>
        @endif

        <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-navy-900">{{ $testimonial->patient_name }}</span>
            <span class="block truncate text-xs text-navy-900/50">
                {{ $testimonial->treatment }} · {{ $testimonial->location }}
            </span>
        </span>
    </figcaption>
</figure>
