@extends('layouts.site')

@section('title', __('gallery.index.meta_title'))
@section('meta_description', __('gallery.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('gallery.index.eyebrow')"
    :title="__('gallery.index.title')"
    :lede="__('gallery.index.lede', ['name' => setting('site_name')])"
    :crumbs="[__('gallery.index.crumb') => null]" />

<section class="section">
    <div class="shell">
        @if ($albums->isEmpty())
            <div class="card p-14 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-mist-100 text-navy-900/30">
                    <x-icon name="image" size="26" />
                </span>
                <p class="mt-5 text-navy-900/60">{{ __('gallery.index.empty') }}</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="70">
                @foreach ($albums as $album)
                    <x-album-card :album="$album" class="reveal" />
                @endforeach
            </div>

            <div class="mt-12">{{ $albums->links() }}</div>
        @endif
    </div>
</section>

@endsection
