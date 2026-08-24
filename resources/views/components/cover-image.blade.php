@props(['path', 'alt' => '', 'aspect' => 'aspect-[21/9]', 'set' => 'cover', 'seed' => null, 'group' => ''])

@php $src = image_url($path, $set, $seed ?? $alt, $group); @endphp

{{-- Upload first, then stand-in photography. With both switched off the page
     keeps the exact layout it had before the panel existed. --}}
@if ($src)
    <div {{ $attributes->merge(['class' => 'shell pt-12 sm:pt-16']) }}>
        <figure class="media-frame reveal reveal-clip shadow-soft">
            <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" data-fade
                 class="{{ $aspect }} w-full object-cover">
        </figure>
    </div>
@endif
