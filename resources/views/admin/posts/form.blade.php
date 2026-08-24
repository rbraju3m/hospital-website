@extends('admin.layouts.app')

@php $editing = $post->exists; @endphp

@section('title', $editing ? __('admin.posts.edit') : __('admin.posts.create'))
@section('heading', $editing ? $post->untranslated('title') : __('admin.posts.create'))

@section('content')
<form method="POST"
      action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
      enctype="multipart/form-data"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }"
      class="max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <a href="{{ route('admin.posts.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="space-y-6">
        <x-admin.section :title="__('admin.form.basics')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="title" :label="__('admin.fields.title')" :model="$post" required class="sm:col-span-2" />
                <x-admin.input name="slug" :label="__('admin.fields.slug')" :value="$post->slug" :help="__('admin.form.slug_help')" />

                <x-admin.select name="category" :label="__('admin.fields.category')" required :value="$post->category"
                                :options="collect(\App\Http\Requests\Admin\PostRequest::CATEGORIES)
                                    ->mapWithKeys(fn ($c) => [$c => category_label('posts', $c)])->all()" />

                <x-admin.translatable name="excerpt" type="textarea" :rows="3" :label="__('admin.fields.excerpt')" :model="$post" class="sm:col-span-2" />
                <x-admin.translatable name="body" type="textarea" :rows="18" :label="__('admin.fields.body')" :model="$post"
                                      :help="__('admin.posts.body_help')" class="sm:col-span-2" />
                <x-admin.image-field name="image" :label="__('admin.fields.image')" :value="$post->untranslated('image')"
                                     set="cover" :seed="$post->id" group="post"
                                     aspect="aspect-[16/9]" class="sm:col-span-2" />
            </div>
        </x-admin.section>

        <x-admin.section :title="__('admin.posts.publishing')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable name="author" :label="__('admin.fields.author')" :model="$post" />
                <x-admin.input name="read_minutes" type="number" :label="__('admin.fields.read_minutes')" required
                               :value="$post->read_minutes ?? 4" />
                <x-admin.input name="published_at" type="datetime-local" :label="__('admin.fields.published_at')"
                               :value="$post->published_at?->format('Y-m-d\TH:i')"
                               :help="__('admin.posts.published_help')" />
                <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <x-admin.toggle name="is_active" :label="__('admin.fields.is_active')" :value="$post->is_active"
                                    :help="__('admin.posts.is_active_help')" />
                    <x-admin.toggle name="is_featured" :label="__('admin.fields.is_featured')" :value="$post->is_featured" />
                </div>
            </div>
        </x-admin.section>
    </div>

    <x-admin.form-actions :cancel="route('admin.posts.index')" />
</form>

@if ($editing)
    <div class="max-w-4xl">
        <x-admin.danger-zone :action="route('admin.posts.destroy', $post)" />
    </div>
@endif
@endsection
