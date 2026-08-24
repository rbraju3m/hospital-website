@extends('layouts.site')

@section('title', __('pages.international.meta_title'))
@section('meta_description', __('pages.international.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('pages.international.eyebrow')"
    :title="__('pages.international.title')"
    :lede="__('pages.international.lede')"
    :crumbs="[__('pages.international.crumb') => null]">

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('contact') }}" class="btn-accent">
            <x-icon name="mail" size="16" /> {{ __('pages.international.estimate_cta') }}
        </a>
        <a href="tel:{{ setting('international_desk') }}"
           class="btn border border-white/25 text-white hover:bg-white/10">
            <x-icon name="phone" size="16" /> {{ setting('international_desk') }}
        </a>
    </div>
</x-page-hero>

<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('pages.international.what_eyebrow')"
            :title="__('pages.international.what_title')"
            :lede="__('pages.international.what_lede')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['file-text', __('pages.international.arrange_1_title'), __('pages.international.arrange_1_body')],
                ['plane', __('pages.international.arrange_2_title'), __('pages.international.arrange_2_body')],
                ['building', __('pages.international.arrange_3_title'), __('pages.international.arrange_3_body')],
                ['globe', __('pages.international.arrange_4_title'), __('pages.international.arrange_4_body')],
                ['credit-card', __('pages.international.arrange_5_title'), __('pages.international.arrange_5_body')],
                ['user-round', __('pages.international.arrange_6_title'), __('pages.international.arrange_6_body')],
            ] as [$icon, $title, $body])
                <div class="card reveal flex flex-col p-7" style="transition-delay: {{ $loop->index * 60 }}ms">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-teal-50 text-teal-700">
                        <x-icon :name="$icon" size="24" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section bg-mist-50">
    <div class="shell grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="h-section">{{ __('pages.international.how_title') }}</h2>
            <ol class="mt-10 space-y-8">
                @foreach ([
                    ...array_map(
                        fn (int $i) => [__("pages.international.how_{$i}_title"), __("pages.international.how_{$i}_body")],
                        range(1, 6)
                    ),
                ] as $i => [$title, $body])
                    <li class="flex gap-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-navy-900 dark:bg-navy-100 font-display text-sm font-bold text-white">
                            {{ $i + 1 }}
                        </span>
                        <div>
                            <p class="font-display text-base font-bold text-navy-900">{{ $title }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-navy-900/65">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <aside class="lg:col-span-5">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <h2 class="font-display text-lg font-bold text-navy-900">{{ __('pages.international.desk_title') }}</h2>
                    <dl class="mt-6 space-y-5 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="phone" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('pages.international.desk_line') }}</dt>
                                <dd><a href="tel:{{ setting('international_desk') }}" class="font-medium text-navy-900 hover:text-teal-700">{{ setting('international_desk') }}</a></dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="mail" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('pages.international.desk_email') }}</dt>
                                <dd><a href="mailto:{{ setting('international_email') }}" class="font-medium text-navy-900 hover:text-teal-700">{{ setting('international_email') }}</a></dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="globe" size="19" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">{{ __('pages.international.desk_languages') }}</dt>
                                <dd class="font-medium text-navy-900">{{ __('pages.international.desk_languages_list') }}</dd>
                            </div>
                        </div>
                    </dl>

                    <a href="{{ route('contact') }}" class="btn-accent mt-7 w-full">{{ __('pages.international.desk_cta') }}</a>
                </div>

                <div class="card p-7">
                    <h2 class="font-display text-base font-bold text-navy-900">{{ __('pages.international.send_title') }}</h2>
                    <ul class="mt-5 space-y-2.5 text-sm text-navy-900/70">
                        @foreach ([
                            ...array_map(fn (int $i) => __("pages.international.send_{$i}"), range(1, 5)),
                        ] as $item)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check" size="15" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</section>

@endsection
