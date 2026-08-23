@extends('admin.layouts.app')

@section('title', $message->subject ?: __('admin.messages.no_subject'))
@section('heading', $message->subject ?: __('admin.messages.no_subject'))
@section('subheading', $message->created_at->translatedFormat('l, j F Y — g:i A'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.messages.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <div class="grid max-w-4xl gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-admin.section :title="__('admin.messages.message')">
                <p class="whitespace-pre-line text-sm leading-relaxed text-navy-900/80">{{ $message->message }}</p>
            </x-admin.section>
        </div>

        <div class="space-y-6">
            <x-admin.section :title="__('admin.messages.sender')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-navy-900/40">{{ __('admin.fields.name') }}</dt>
                        <dd class="mt-0.5 text-navy-900">{{ $message->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-navy-900/40">{{ __('admin.fields.phone') }}</dt>
                        <dd class="mt-0.5"><a href="tel:{{ $message->phone }}" class="text-teal-700 hover:underline">{{ $message->phone }}</a></dd>
                    </div>
                    @if ($message->email)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-navy-900/40">{{ __('admin.fields.email') }}</dt>
                            <dd class="mt-0.5 break-words"><a href="mailto:{{ $message->email }}" class="text-teal-700 hover:underline">{{ $message->email }}</a></dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 grid gap-2">
                    <a href="tel:{{ $message->phone }}" class="btn-accent btn-sm">
                        <x-icon name="phone" size="15" />
                        {{ __('admin.messages.call_back') }}
                    </a>

                    <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-outline btn-sm w-full">
                            {{ $message->is_read ? __('admin.messages.mark_unread') : __('admin.messages.mark_read') }}
                        </button>
                    </form>
                </div>
            </x-admin.section>

            <x-admin.danger-zone :action="route('admin.messages.destroy', $message)" class="!mt-6" />
        </div>
    </div>
@endsection
