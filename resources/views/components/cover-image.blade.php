@props(['path', 'alt' => '', 'aspect' => 'aspect-[21/9]'])

{{-- Renders nothing until someone uploads one, so a page with no cover keeps
     the exact layout it had before the panel existed. --}}
@if ($path)
    <div {{ $attributes->merge(['class' => 'shell pt-12 sm:pt-16']) }}>
        <img src="{{ media_url($path) }}" alt="{{ $alt }}" loading="lazy"
             class="{{ $aspect }} w-full rounded-[1.25rem] object-cover shadow-soft">
    </div>
@endif
