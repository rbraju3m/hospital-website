@extends('layouts.site')

@section('title', __('posts.index.meta_title'))
@section('meta_description', __('posts.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('posts.index.eyebrow')"
    :title="__('posts.index.title')"
    :lede="__('posts.index.lede')"
    :crumbs="[__('posts.index.crumb') => null]" />

<section class="section">
    <div class="shell">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('posts.index') }}" @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>{{ __('posts.index.all') }}</a>
            @foreach ($categories as $cat)
                <a href="{{ route('posts.index', ['category' => $cat]) }}"
                   @class(['btn-sm', $category === $cat ? 'btn-primary' : 'btn-outline'])>{{ category_label('posts', $cat) }}</a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <div class="card mt-10 p-14 text-center">
                <p class="text-navy-900/60">{{ __('posts.index.empty') }}</p>
            </div>
        @else
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" class="reveal" style="transition-delay: {{ $loop->index * 50 }}ms" />
                @endforeach
            </div>

            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </div>
</section>

@endsection
