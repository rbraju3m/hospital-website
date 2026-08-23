@extends('layouts.site')

@section('title', __('diagnostics.index.meta_title'))
@section('meta_description', __('diagnostics.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('diagnostics.index.eyebrow')"
    :title="__('diagnostics.index.title')"
    :lede="__('diagnostics.index.lede')"
    :crumbs="[__('diagnostics.index.crumb') => null]">

    <form method="GET" action="{{ route('diagnostics.index') }}" class="flex w-full max-w-xl flex-wrap gap-2">
        @if ($category)
            <input type="hidden" name="category" value="{{ $category }}">
        @endif

        <label class="relative min-w-0 flex-1">
            <span class="sr-only">{{ __('diagnostics.index.search_label') }}</span>
            <x-icon name="search" size="18"
                    class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-navy-900/35" />
            <input type="search" name="q" value="{{ $term }}"
                   placeholder="{{ __('diagnostics.index.search_placeholder') }}"
                   class="input ps-11">
        </label>

        <button type="submit" class="btn-accent">{{ __('diagnostics.index.filter') }}</button>
    </form>
</x-page-hero>

<section class="section">
    <div class="shell">

        {{-- Category filter. Counts come from the search-narrowed set, not the
             category-narrowed one, or every chip would read zero once chosen. --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('diagnostics.index', array_filter(['q' => $term])) }}"
               @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>
                {{ __('diagnostics.index.all_categories') }}
                <span class="opacity-60">{{ $counts->sum() }}</span>
            </a>

            @foreach (\App\Http\Controllers\Web\DiagnosticController::CATEGORIES as $slug)
                @continue(($counts[$slug] ?? 0) === 0)
                <a href="{{ route('diagnostics.index', array_filter(['q' => $term, 'category' => $slug])) }}"
                   @class(['btn-sm', $category === $slug ? 'btn-primary' : 'btn-outline'])>
                    {{ category_label('diagnostics', $slug) }}
                    <span class="opacity-60">{{ $counts[$slug] }}</span>
                </a>
            @endforeach

            @if ($term || $category)
                <a href="{{ route('diagnostics.index') }}" class="btn-ghost btn-sm">{{ __('diagnostics.index.reset') }}</a>
            @endif
        </div>

        <p class="mt-6 text-sm text-navy-900/55">
            {{ trans_choice('diagnostics.index.found', $tests->total(), ['count' => number_format($tests->total())]) }}
            @if ($term)
                {{ __('diagnostics.index.found_for', ['term' => $term]) }}
            @endif
        </p>

        @if ($tests->isEmpty())
            <div class="card mt-8 p-14 text-center">
                <p class="font-medium text-navy-900">{{ __('diagnostics.index.empty') }}</p>
                <p class="mt-2 text-sm text-navy-900/55">{{ __('diagnostics.index.empty_hint') }}</p>
                <a href="tel:{{ setting('hotline') }}" class="btn-outline btn-sm mt-6">
                    <x-icon name="phone" size="15" /> {{ setting('hotline') }}
                </a>
            </div>
        @else
            <div class="card mt-8 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem]">
                        <caption class="sr-only">{{ __('diagnostics.index.title') }}</caption>
                        <tbody>
                            @foreach ($tests as $test)
                                <tr class="border-t border-mist-200 first:border-0 transition hover:bg-mist-50">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('diagnostics.show', $test) }}"
                                           class="font-display text-base font-bold text-navy-900 hover:text-teal-700">
                                            {{ $test->name }}
                                        </a>
                                        <p class="mt-1 line-clamp-1 text-sm text-navy-900/55">{{ $test->summary }}</p>

                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="chip">{{ category_label('diagnostics', $test->category) }}</span>
                                            @if ($test->code)
                                                <span class="chip font-mono text-[11px]">{{ $test->code }}</span>
                                            @endif
                                            @if ($test->is_home_collection)
                                                <span class="chip-accent">
                                                    <x-icon name="droplet" size="12" />
                                                    {{ __('diagnostics.index.home_collection_short') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="hidden px-5 py-4 text-sm text-navy-900/60 sm:table-cell">
                                        @if ($test->report_time)
                                            <span class="flex items-center gap-1.5">
                                                <x-icon name="clock" size="14" class="text-teal-600" />
                                                {{ $test->report_time }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-end">
                                        <span class="font-display text-lg font-extrabold text-navy-900">
                                            ৳{{ number_format($test->effectivePrice()) }}
                                        </span>
                                        @if ($test->savingsPercent())
                                            <span class="block text-xs text-navy-900/40 line-through">
                                                ৳{{ number_format($test->price) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">{{ $tests->links() }}</div>
        @endif

        <p class="mt-8 text-xs text-navy-900/45">{{ __('diagnostics.index.prices_note') }}</p>
    </div>
</section>

@endsection
