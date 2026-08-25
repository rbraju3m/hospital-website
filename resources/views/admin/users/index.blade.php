@extends('admin.layouts.app')

@section('title', __('admin.nav.users'))
@section('heading', __('admin.nav.users'))
@section('subheading', __('admin.users.intro'))

@section('content')
    <x-admin.list-header :create-href="route('admin.users.create')"
                         :create-label="__('admin.users.create')"
                         :search="false" />

    <div class="admin-card max-w-3xl overflow-hidden">
        <ul>
            @foreach ($users as $user)
                <li class="admin-row flex items-center gap-4 px-5 py-4 first:border-0">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-navy-900 dark:bg-navy-100 text-xs font-bold text-white">
                        {{ Str::upper(Str::substr($user->name, 0, 2)) }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-navy-900">
                            {{ $user->name }}
                            @if ($user->is(auth()->user()))
                                <span class="ms-1 badge-teal">{{ __('admin.users.you') }}</span>
                            @endif
                        </span>
                        <span class="block truncate text-xs text-navy-900/50">{{ $user->email }}</span>
                    </span>

                    <span class="hidden shrink-0 sm:block">
                        <span class="{{ $user->isAdministrator() ? 'badge-navy' : 'badge-slate' }}">{{ $user->roleLabel() }}</span>
                    </span>

                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-outline btn-sm">{{ __('admin.actions.edit') }}</a>

                    @unless ($user->is(auth()->user()))
                        <x-admin.delete-form :action="route('admin.users.destroy', $user)"
                                             :confirm="__('admin.users.confirm_delete', ['name' => $user->name])" compact />
                    @endunless
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
