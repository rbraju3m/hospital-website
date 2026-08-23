@extends('layouts.site')

@section('title', 'Health Hub')
@section('meta_description', 'Health guidance from RBR Hospital consultants — dengue warning signs, chest pain, diabetes control and preventive screening.')

@section('content')

<x-page-hero
    eyebrow="Health Hub"
    title="Guidance from our consultants"
    lede="Practical, locally relevant health writing — when to worry, when not to, and what to do about it."
    :crumbs="['Health Hub' => null]" />

<section class="section">
    <div class="shell">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('posts.index') }}" @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>All articles</a>
            @foreach ($categories as $cat)
                <a href="{{ route('posts.index', ['category' => $cat]) }}"
                   @class(['btn-sm', $category === $cat ? 'btn-primary' : 'btn-outline'])>{{ str($cat)->headline() }}</a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <div class="card mt-10 p-14 text-center">
                <p class="text-navy-900/60">No articles in this category yet.</p>
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
