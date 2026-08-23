@props(['department'])

<article {{ $attributes->merge(['class' => 'card-interactive group relative flex h-full flex-col p-7']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-50 text-teal-700 transition
                 duration-300 group-hover:bg-teal-600 group-hover:text-white">
        <x-icon :name="$department->icon" size="24" />
    </span>

    <h3 class="mt-5 font-display text-lg font-bold leading-snug text-navy-900">
        <a href="{{ route('departments.show', $department) }}" class="after:absolute after:inset-0 group-hover:text-teal-700">
            {{ $department->name }}
        </a>
    </h3>

    <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $department->tagline }}</p>

    @if ($department->doctors_count ?? false)
        <p class="mt-4 text-xs font-medium text-navy-900/45">{{ $department->doctors_count }} consultants</p>
    @endif

    <span class="mt-auto pt-5 text-sm font-semibold text-teal-700 transition group-hover:translate-x-0.5">
        Explore department →
    </span>
</article>
