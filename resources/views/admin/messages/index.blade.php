@extends('admin.layouts.app')

@section('title', __('admin.nav.messages'))
@section('heading', __('admin.nav.messages'))
@section('subheading', trans_choice('admin.messages.unread_count', $unreadCount, ['count' => $unreadCount]))

@section('content')
    <x-admin.list-header :placeholder="__('admin.messages.search')">
        <select name="unread" class="input input-sm w-auto">
            <option value="">{{ __('admin.messages.all') }}</option>
            <option value="1" @selected(request('unread'))>{{ __('admin.messages.unread_only') }}</option>
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($messages->isEmpty())
            <x-admin.empty :message="__('admin.messages.empty')" icon="inbox" />
        @else
            <ul>
                @foreach ($messages as $message)
                    <li class="admin-row first:border-0">
                        <a href="{{ route('admin.messages.show', $message) }}" class="flex items-start gap-4 px-5 py-4">
                            <span @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-teal-500' => ! $message->is_read,
                                'bg-mist-200' => $message->is_read,
                            ])></span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2">
                                    <span @class([
                                        'truncate text-sm text-navy-900',
                                        'font-bold' => ! $message->is_read,
                                        'font-medium' => $message->is_read,
                                    ])>{{ $message->name }}</span>
                                    <span class="text-xs text-navy-900/40">{{ $message->phone }}</span>
                                </span>

                                <span class="mt-1 block truncate text-sm font-medium text-navy-900/75">
                                    {{ $message->subject ?: __('admin.messages.no_subject') }}
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-navy-900/50">{{ $message->message }}</span>
                            </span>

                            <span class="shrink-0 text-xs text-navy-900/40">
                                {{ $message->created_at->translatedFormat('j M') }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-5">{{ $messages->links() }}</div>
@endsection
