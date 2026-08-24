@props(['doctor', 'size' => 'md', 'rounded' => 'rounded-2xl'])

@php
    $dimensions = [
        'sm' => 'h-14 w-14 text-base',
        'md' => 'h-20 w-20 text-xl',
        'lg' => 'h-28 w-28 text-3xl',
        'xl' => 'h-40 w-40 text-5xl',
    ][$size];

    // Upload first, then stand-in photography seeded by the row id, so a
    // consultant keeps the same face on the listing, the profile and the
    // booking page — and no two of them share one.
    $photo = doctor_photo($doctor);
@endphp

@if ($photo)
    <img src="{{ $photo }}" alt="{{ $doctor->name }}" loading="lazy" data-fade
         {{ $attributes->merge(['class' => "$dimensions $rounded shrink-0 bg-mist-100 object-cover object-top"]) }}>
@else
    {{-- No photo, and stand-ins switched off: an initials tile reads better
         than a generic silhouette. --}}
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => "$dimensions $rounded grid shrink-0 place-items-center bg-gradient-to-br from-navy-800 to-navy-950 font-display font-bold text-white/90"]) }}>
        {{ $doctor->initials() }}
    </span>
@endif
