@extends('layouts.site')

@php
    /* One list drives the grid, the viewer and the thumbnail strip, so a tile
       and the slide it opens can never drift apart. Photos with nothing to show
       — no upload, and stand-in imagery switched off — are dropped here rather
       than rendered as an empty frame. */
    $slides = $photos
        ->map(fn ($photo) => ['src' => $photo->url(), 'caption' => $photo->caption])
        ->filter(fn ($slide) => filled($slide['src']))
        ->values();
@endphp

@section('title', $album->title)
@section('meta_description', $album->summary ?: __('gallery.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('gallery.index.eyebrow')"
    :title="$album->title"
    :lede="$album->summary"
    :crumbs="[__('gallery.index.crumb') => route('gallery.index'), $album->title => null]" />

<section class="section">
    <div class="shell">
        @if ($album->description)
            <div class="reveal mx-auto mb-12 max-w-3xl space-y-5 text-center text-base leading-relaxed text-navy-900/70">
                @foreach (preg_split('/\n+/', $album->description) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif

        @if ($slides->isEmpty())
            <div class="card p-14 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-mist-100 text-navy-900/30">
                    <x-icon name="image" size="26" />
                </span>
                <p class="mt-5 text-navy-900/60">{{ __('gallery.album.empty') }}</p>
                <a href="{{ route('gallery.index') }}" class="btn-outline btn-sm mt-6">
                    <x-icon name="arrow-left" size="16" />
                    {{ __('gallery.album.back') }}
                </a>
            </div>
        @else
            <div x-data="galleryLightbox(@js($slides))" @keydown.window="onKey($event)">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4" data-reveal-stagger="50">
                    @foreach ($slides as $slide)
                        {{-- A real link to the file, so the grid still opens
                             photographs with the viewer unavailable. --}}
                        <a href="{{ $slide['src'] }}"
                           @click.prevent="open({{ $loop->index }}, $event.currentTarget)"
                           class="media-frame reveal reveal-clip group/tile relative block aspect-[4/3]
                                  focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500"
                           aria-label="{{ $slide['caption'] ?: __('gallery.lightbox.open') }}">
                            <img src="{{ $slide['src'] }}" alt="{{ $slide['caption'] }}" loading="lazy" data-fade
                                 class="h-full w-full object-cover">

                            <span aria-hidden="true"
                                  class="absolute inset-0 grid place-items-center bg-navy-950/0 dark:bg-navy-50/0 text-white/0
                                         transition duration-300 ease-out
                                         group-hover/tile:bg-navy-950/35 tile:dark:bg-navy-50/35 group-hover/tile:text-white">
                                <x-icon name="maximize" size="22" />
                            </span>

                            @if ($slide['caption'])
                                <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-navy-950/80 dark:from-navy-50/80 to-transparent
                                             px-3 pb-2.5 pt-8 text-start text-xs font-medium text-white">
                                    {{ $slide['caption'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @include('pages.gallery.viewer', ['album' => $album, 'slides' => $slides])
            </div>
        @endif
    </div>
</section>

@if ($more->isNotEmpty())
<section class="section bg-mist-50 pt-0">
    <div class="shell">
        <x-section-heading
            :title="__('gallery.album.more')"
            :link="route('gallery.index')"
            :link-label="__('gallery.album.all')"
            class="reveal" />

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="70">
            @foreach ($more as $other)
                <x-album-card :album="$other" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
