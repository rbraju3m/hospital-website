@extends('admin.layouts.app')

@section('title', __('admin.nav.posts'))
@section('heading', __('admin.nav.posts'))
@section('subheading', trans_choice('admin.posts.count', $posts->total(), ['count' => number_format($posts->total())]))

@section('content')
    <x-admin.list-header :create-href="route('admin.posts.create')"
                         :create-label="__('admin.posts.create')"
                         :placeholder="__('admin.posts.search')">
        <select name="category" class="input input-sm w-auto">
            <option value="">{{ __('admin.posts.all_categories') }}</option>
            @foreach (\App\Http\Requests\Admin\PostRequest::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>
                    {{ category_label('posts', $category) }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden"
         x-data="adminList({ list: 'posts', sortable: false,
             labels: { saved: @js(__('admin.lists.saved')), failed: @js(__('admin.lists.failed')) } })">
        <x-admin.list-status />

        @if ($posts->isEmpty())
            <x-admin.empty :message="__('admin.posts.empty')" :action="__('admin.posts.create')"
                           :href="route('admin.posts.create')" icon="newspaper" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem]">
                    <thead class="bg-mist-50">
                        <tr>
                            <th class="admin-th">{{ __('admin.fields.title') }}</th>
                            <th class="admin-th">{{ __('admin.fields.category') }}</th>
                            <th class="admin-th">{{ __('admin.fields.published_at') }}</th>
                            <th class="admin-th">{{ __('admin.lists.live') }}</th>
                            <th class="admin-th text-end">{{ __('admin.actions.label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="admin-row" data-id="{{ $post->id }}">
                                <td class="admin-td">
                                    <div class="flex items-center gap-3">
                                        @if ($post->untranslated('image'))
                                            <img src="{{ media_url($post->untranslated('image')) }}" alt=""
                                                 class="h-10 w-14 shrink-0 rounded-lg object-cover">
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.posts.edit', $post) }}"
                                               class="block truncate font-semibold text-navy-900 hover:text-teal-700">
                                                {{ $post->untranslated('title') }}
                                            </a>
                                            <span class="block truncate text-xs text-navy-900/45">{{ $post->untranslated('author') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-td">{{ category_label('posts', $post->category) }}</td>
                                <td class="admin-td">
                                    @if (! $post->published_at)
                                        <span class="badge-slate">{{ __('admin.posts.draft') }}</span>
                                    @elseif ($post->published_at->isFuture())
                                        <span class="badge-amber">{{ __('admin.posts.scheduled') }}</span>
                                        <span class="ms-1 text-xs text-navy-900/45">{{ $post->published_at->translatedFormat('j M Y') }}</span>
                                    @else
                                        {{ $post->published_at->translatedFormat('j M Y') }}
                                    @endif
                                </td>
                                <td class="admin-td">
                                    <div class="flex items-center gap-2">
                                        <x-admin.live-switch :model="$post" />
                                        <x-admin.translation-state :model="$post" compact />
                                    </div>
                                </td>
                                <td class="admin-td text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('posts.show', $post) }}" target="_blank" rel="noopener"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.view_public') }}">
                                            <x-icon name="external-link" size="16" />
                                        </a>
                                        <a href="{{ route('admin.posts.edit', $post) }}"
                                           class="rounded-lg p-2 text-navy-900/40 hover:bg-mist-100 hover:text-navy-900"
                                           title="{{ __('admin.actions.edit') }}">
                                            <x-icon name="pencil" size="16" />
                                        </a>
                                        <x-admin.delete-form :action="route('admin.posts.destroy', $post)" compact />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-5">{{ $posts->links() }}</div>
@endsection
