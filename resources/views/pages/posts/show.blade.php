@extends('layouts.site')

@section('title', $post->title)
@section('meta_description', $post->excerpt)

@push('head')
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
@endpush

@section('content')

<article>
    <x-page-hero
        :eyebrow="str($post->category)->headline()"
        :title="$post->title"
        :lede="$post->excerpt"
        :crumbs="[__('posts.show.crumb') => route('posts.index'), Str::limit($post->title, 40) => null]">

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/60">
            <span class="flex items-center gap-2">
                <x-icon name="user-round" size="16" class="text-teal-300" />
                {{ $post->author }}
            </span>
            <span class="flex items-center gap-2">
                <x-icon name="calendar" size="16" class="text-teal-300" />
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j F Y') }}</time>
            </span>
            <span class="flex items-center gap-2">
                <x-icon name="clock" size="16" class="text-teal-300" />
                {{ __('common.read_time', ['count' => $post->read_minutes]) }}
            </span>
        </div>
    </x-page-hero>

    <section class="section">
        <div class="shell grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <x-article-body :body="$post->body" />

                <div class="mt-14 rounded-[1.25rem] border border-mist-200 bg-mist-50 p-7">
                    <p class="text-sm leading-relaxed text-navy-900/60">
                        <span class="font-semibold text-navy-900">{{ __('posts.show.disclaimer_lead') }}</span>
                        {{ __('posts.show.disclaimer_body') }}
                        <a href="tel:{{ setting('hotline') }}" class="font-semibold text-teal-700 hover:underline">{{ setting('hotline') }}</a>.
                    </p>
                </div>
            </div>

            <aside class="lg:col-span-4">
                <div class="sticky top-24 space-y-4">
                    <div class="card p-7">
                        <h2 class="font-display text-base font-bold text-navy-900">{{ __('posts.show.specialist_title') }}</h2>
                        <p class="mt-3 text-sm text-navy-900/60">
                            {{ __('posts.show.specialist_body', ['count' => setting('stat_departments')]) }}
                        </p>
                        <a href="{{ route('appointment.create') }}" class="btn-accent mt-5 w-full">{{ __('common.book_appointment') }}</a>
                        <a href="{{ route('doctors.index') }}" class="btn-outline mt-2.5 w-full">{{ __('common.find_a_doctor') }}</a>
                    </div>

                    <div class="card bg-urgent-50 p-7">
                        <div class="flex items-center gap-3">
                            <x-icon name="ambulance" size="22" class="text-urgent-600" />
                            <h2 class="font-display text-base font-bold text-navy-900">{{ __('posts.show.emergency_title') }}</h2>
                        </div>
                        <p class="mt-3 text-sm text-navy-900/65">{{ __('posts.show.emergency_body') }}</p>
                        <a href="tel:{{ setting('hotline') }}" class="btn-urgent mt-5 w-full">
                            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</article>

@if ($related->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading :eyebrow="__('posts.show.related_eyebrow')"
                               :title="__('posts.show.related_title')"
                               :link="route('posts.index')"
                               :link-label="__('posts.show.related_link')" class="reveal" />
            <div class="mt-12 grid gap-5 md:grid-cols-3">
                @foreach ($related as $other)
                    <x-post-card :post="$other" class="reveal" />
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
