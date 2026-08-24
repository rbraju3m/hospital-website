@extends('admin.layouts.app')

@php $editing = $user->exists; @endphp

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

    <x-admin.form-actions :cancel="route('admin.users.index')" />
</form>
@endsection
