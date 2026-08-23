@extends('layouts.site')

@section('title', __('packages.index.meta_title'))
@section('meta_description', __('packages.index.meta_description', ['name' => setting('site_name')]))

@section('content')

<x-page-hero
    :eyebrow="__('packages.index.eyebrow')"
    :title="__('packages.index.title')"
    :lede="__('packages.index.lede')"
    :crumbs="[__('packages.index.crumb') => null]" />

<section class="section">
    <div class="shell">

        {{-- Category filter --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('packages.index') }}"
               @class(['btn-sm', $category ? 'btn-outline' : 'btn-primary'])>{{ __('packages.index.all') }}</a>
            @foreach ($categories as $cat)
                <a href="{{ route('packages.index', ['category' => $cat]) }}"
                   @class(['btn-sm', $category === $cat ? 'btn-primary' : 'btn-outline'])>
                    {{ category_label('packages', $cat) }}
                </a>
            @endforeach
        </div>

        @if ($packages->isEmpty())
            <div class="card mt-10 p-14 text-center">
                <p class="text-navy-900/60">{{ __('packages.index.empty') }}</p>
            </div>
        @else
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-package-card :package="$package" class="reveal"
                                    style="transition-delay: {{ $loop->index * 50 }}ms" />
                @endforeach
            </div>
        @endif

        <div class="reveal card mt-14 flex flex-col items-center gap-6 p-10 text-center sm:p-14">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-teal-50 text-teal-700">
                <x-icon name="users" size="28" />
            </span>
            <div class="max-w-xl">
                <h2 class="font-display text-2xl font-bold text-navy-900">{{ __('packages.index.corporate_title') }}</h2>
                <p class="mt-3 text-navy-900/60">{{ __('packages.index.corporate_body') }}</p>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('contact') }}" class="btn-accent">{{ __('packages.index.corporate_cta') }}</a>
                <a href="tel:{{ setting('appointment_number') }}" class="btn-outline">
                    <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
