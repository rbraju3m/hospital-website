@props(['doctor', 'size' => 'md'])

@php
    $dimensions = [
        'sm' => 'h-14 w-14 text-base',
        'md' => 'h-20 w-20 text-xl',
        'lg' => 'h-28 w-28 text-3xl',
        'xl' => 'h-40 w-40 text-5xl',
    ][$size];
@endphp

@if ($doctor->photo)
    <img src="{{ media_url($doctor->untranslated('photo')) }}" alt="{{ $doctor->name }}" loading="lazy"
         {{ $attributes->merge(['class' => "$dimensions rounded-2xl object-cover"]) }}>
@else
    {{-- No photo on file: an initials tile reads better than a generic silhouette. --}}
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => "$dimensions grid shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-navy-800 to-navy-950 font-display font-bold text-white/90"]) }}>
        {{ $doctor->initials() }}
    </span>
@endif
