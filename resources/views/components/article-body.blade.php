@props(['body'])

{{-- Renders the small markup language the panel's editor writes:
     `## heading`, `### subheading`, `- bullet`, `1. numbered`, `> quote`,
     `---` and the inline markers in inline_markup(). Content is escaped before
     any markup is re-introduced, so the only HTML here is HTML we wrote.

     resources/js/app.js carries a mirror of this for the editor's preview. If
     one changes, the other changes with it — a preview that lies is worse than
     no preview. --}}
@if (filled($body))
<div {{ $attributes->merge(['class' => 'space-y-5 text-base leading-relaxed text-navy-900/75']) }}>
    @foreach (preg_split('/\n\n+/', trim($body)) as $block)
        @php
            $block = trim($block);
            $lines = explode("\n", $block);
        @endphp

        @if ($block === '')
            @continue

        @elseif (str_starts_with($block, '## '))
            <h2 class="!mt-12 font-display text-2xl font-bold text-navy-900">{{ substr($block, 3) }}</h2>

        @elseif (str_starts_with($block, '### '))
            <h3 class="!mt-8 font-display text-lg font-bold text-navy-900">{{ substr($block, 4) }}</h3>

        @elseif (str_starts_with($block, '---'))
            <hr class="!my-10 border-mist-200">

        @elseif (str_starts_with($block, '> '))
            <blockquote class="border-s-4 border-teal-500 ps-5 text-navy-900/80 italic">
                {!! inline_markup(implode(' ', array_map(fn ($line) => ltrim($line, '> '), $lines))) !!}
            </blockquote>

        @elseif (str_starts_with($block, '- '))
            <ul class="space-y-2.5 pl-1">
                @foreach ($lines as $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"></span>
                        <span>{!! inline_markup(ltrim($item, '- ')) !!}</span>
                    </li>
                @endforeach
            </ul>

        @elseif (preg_match('/^\d+\.\s/', $block))
            <ol class="space-y-2.5 pl-1">
                @foreach ($lines as $index => $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-teal-50
                                     text-[11px] font-bold text-teal-700">{{ $index + 1 }}</span>
                        <span>{!! inline_markup(preg_replace('/^\d+\.\s*/', '', $item)) !!}</span>
                    </li>
                @endforeach
            </ol>

        @else
            <p>{!! inline_markup($block) !!}</p>
        @endif
    @endforeach
</div>
@endif
