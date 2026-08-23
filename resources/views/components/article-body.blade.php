@props(['body'])

{{-- Renders the markdown-lite used in seeded article bodies: `## heading`,
     `- bullet` lists and **bold**. Content is escaped before any markup is
     re-introduced (see inline_markup()). --}}
<div {{ $attributes->merge(['class' => 'space-y-5 text-base leading-relaxed text-navy-900/75']) }}>
    @foreach (preg_split('/\n\n+/', trim($body)) as $block)
        @php $block = trim($block); @endphp

        @if (str_starts_with($block, '## '))
            <h2 class="!mt-12 font-display text-2xl font-bold text-navy-900">{{ substr($block, 3) }}</h2>

        @elseif (str_starts_with($block, '- '))
            <ul class="space-y-2.5 pl-1">
                @foreach (explode("\n", $block) as $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"></span>
                        <span>{!! inline_markup(ltrim($item, '- ')) !!}</span>
                    </li>
                @endforeach
            </ul>

        @else
            <p>{!! inline_markup($block) !!}</p>
        @endif
    @endforeach
</div>
