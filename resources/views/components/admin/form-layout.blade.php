{{-- Two columns on a wide screen: the record itself on the left, the switches
     and the picture that decide how it is published on the right. Below xl the
     aside falls back under the main column, in the same order the page reads.

     Sections placed in the aside must use a single-column field grid — the
     column is ~22rem wide but Tailwind's `sm:` still measures the viewport, so
     a `sm:grid-cols-2` inside it would crush two fields into that width. --}}
<div {{ $attributes->merge(['class' => 'grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_21rem] 2xl:grid-cols-[minmax(0,1fr)_24rem]']) }}>
    <div class="min-w-0 space-y-6">{{ $slot }}</div>

    @isset($aside)
        <div class="min-w-0 space-y-6">{{ $aside }}</div>
    @endisset
</div>
