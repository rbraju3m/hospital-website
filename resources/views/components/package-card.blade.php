@props(['package'])

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col overflow-hidden']) }}>
    @if ($package->savingsPercent())
        <span class="absolute right-5 top-5 z-10 rounded-full bg-teal-600 px-2.5 py-1 text-[11px] font-bold text-white
                     shadow-soft transition duration-300 ease-out group-hover:scale-105">
            {{ __('packages.show.save', ['percent' => $package->savingsPercent()]) }}
        </span>
    @endif

    <div class="flex flex-1 flex-col p-7">
        <p class="eyebrow">{{ category_label('packages', $package->category) }}</p>

        <h3 class="mt-3 font-display text-xl font-bold leading-snug text-navy-900">
            <a href="{{ route('packages.show', $package) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
                {{ $package->name }}
            </a>
        </h3>

        <p class="mt-3 text-sm leading-relaxed text-navy-900/60">{{ $package->summary }}</p>

        <ul class="mt-5 space-y-2">
            @foreach (array_slice($package->tests ?? [], 0, 4) as $test)
                <li class="flex items-start gap-2 text-sm text-navy-900/70">
                    <x-icon name="check" size="15" class="mt-0.5 text-teal-600" stroke="2.5" />
                    {{ $test }}
                </li>
            @endforeach
            @if (count($package->tests ?? []) > 4)
                <li class="pl-6 text-sm font-medium text-navy-900/45">
                    {{ __('packages.more_tests', ['count' => count($package->tests) - 4]) }}
                </li>
            @endif
        </ul>

        <div class="mt-auto flex items-end justify-between gap-4 border-t border-mist-200 pt-5">
            <div>
                <div class="flex items-baseline gap-2">
                    <span class="font-display text-2xl font-extrabold text-navy-900">
                        ৳{{ number_format($package->effectivePrice()) }}
                    </span>
                    @if ($package->discount_price)
                        <span class="text-sm text-navy-900/40 line-through">৳{{ number_format($package->price) }}</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-navy-900/50">{{ $package->duration }}</p>
            </div>

            <span class="card-arrow relative z-10 text-sm font-semibold text-teal-700">
                {{ __('common.details') }} →
            </span>
        </div>
    </div>
</article>
