@props(['name', 'label', 'value' => false, 'help' => null, 'id' => null])

@php $id ??= 'sw-'.Str::slug(str_replace(['[', ']'], '-', $name)); @endphp

{{-- A switch, not a checkbox: these read as "this part of the site is on air",
     and a physical-feeling control is the right register for that. No hidden
     "0" companion — the controller writes every known key from the registry,
     so an unchecked box posting nothing is exactly what it should do.

     The input sits immediately before the track because that adjacency is what
     `.switch` styles the checked state through. --}}
<label for="{{ $id }}"
       {{ $attributes->merge(['class' => 'group flex cursor-pointer items-start gap-4 rounded-xl px-3 py-3 transition duration-200 ease-out hover:bg-mist-50']) }}>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-medium text-navy-900">{{ $label }}</span>
        @if ($help)
            <span class="mt-0.5 block text-xs leading-relaxed text-navy-900/45">{{ $help }}</span>
        @endif
    </span>

    <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked($value)
           class="sr-only" data-site-switch>

    <span class="switch mt-0.5" aria-hidden="true"><span></span></span>
</label>
