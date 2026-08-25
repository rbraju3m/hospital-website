@php use App\Support\StaffRoles; @endphp
@extends('admin.layouts.app')

@php
    $editing = $user->exists;

    // Administrator last, so the default landing on a new account is the least
    // powerful role on the list rather than the most.
    $roles = collect([StaffRoles::FRONT_DESK, StaffRoles::EDITOR, StaffRoles::ADMINISTRATOR])
        ->mapWithKeys(fn (string $role) => [$role => StaffRoles::label($role)]);
@endphp

@section('title', $editing ? __('admin.users.edit') : __('admin.users.create'))
@section('heading', $editing ? $user->name : __('admin.users.create'))

@section('content')
<form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}"
      class="w-full max-w-3xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="mb-5">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-navy-900/50 hover:text-navy-900">
            ← {{ __('admin.actions.back') }}
        </a>
    </div>

    <x-admin.section :title="__('admin.users.account')">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" :label="__('admin.fields.name')" required :value="$user->name" />
            <x-admin.input name="email" type="email" :label="__('admin.fields.email')" required :value="$user->email"
                           autocomplete="username" />

            <x-admin.input name="password" type="password" :label="__('admin.fields.password')" :required="! $editing"
                           autocomplete="new-password"
                           :help="$editing ? __('admin.users.password_optional') : __('admin.users.password_help')" />

            <x-admin.input name="password_confirmation" type="password" :label="__('admin.fields.password_confirmation')"
                           :required="! $editing" autocomplete="new-password" />
        </div>
    </x-admin.section>

    <x-admin.section :title="__('admin.users.role')" :description="__('admin.users.role_help')">
        @if ($editing && $user->is(auth()->user()))
            {{-- Your own role is not yours to change. Demoting yourself takes
                 this screen with it, and the way back is a database client. --}}
            <p class="flex items-center gap-2 text-sm text-navy-900/60">
                <x-icon name="shield" size="16" class="text-navy-900/35" />
                {{ __('admin.users.role_self', ['role' => $user->roleLabel()]) }}
            </p>
        @else
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="role" :label="__('admin.fields.role')" required
                                :value="$user->role ?: $roles->keys()->first()"
                                :options="$roles->all()" />
            </div>

            <ul class="mt-4 space-y-1.5 text-xs text-navy-900/55">
                @foreach ($roles as $role => $label)
                    <li>
                        <span class="font-semibold text-navy-900/75">{{ $label }}</span>
                        — {{ __("admin.roles.{$role}_help") }}
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.section>

    <x-admin.form-actions :cancel="route('admin.users.index')" />
</form>
@endsection
