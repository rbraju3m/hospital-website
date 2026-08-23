@props(['rating' => 5])

<div {{ $attributes->merge(['class' => 'flex items-center gap-0.5']) }}
     role="img" aria-label="{{ $rating }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        <x-icon name="star" size="15"
                class="{{ $i <= $rating ? 'fill-amber-400 text-amber-400' : 'text-navy-200' }}" />
    @endfor
</div>
