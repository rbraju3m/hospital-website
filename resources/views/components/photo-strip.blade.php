@props(['photos', 'columns' => 'sm:grid-cols-3 lg:grid-cols-4'])

{{-- A row of photographs lifted out of the gallery. Each tile links to the
     album it belongs to rather than opening on the spot: the lightbox lives on
     the album page, and a visitor who wants one picture wants the set. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-4 '.$columns]) }} data-reveal-stagger="60">
    @foreach ($photos as $photo)
        <a href="{{ route('gallery.show', $photo->album) }}"
           class="media-frame reveal reveal-clip group/photo relative block aspect-[4/3]">
            <img src="{{ $photo->url() }}" alt="{{ $photo->caption }}" loading="lazy" data-fade
                 class="h-full w-full object-cover">

            <span aria-hidden="true" class="media-scrim opacity-0 transition duration-300 group-hover/photo:opacity-100"></span>

            <span class="absolute inset-x-0 bottom-0 translate-y-2 px-3 pb-3 text-start text-xs font-semibold text-white
                         opacity-0 transition duration-300 ease-out
                         group-hover/photo:translate-y-0 group-hover/photo:opacity-100">
                {{ $photo->album->title }}
            </span>
        </a>
    @endforeach
</div>
