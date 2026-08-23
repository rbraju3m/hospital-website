@extends('admin.layouts.app')

@section('title', __('admin.nav.patients'))
@section('heading', __('admin.nav.patients'))
@section('subheading', __('admin.patients.intro'))

@section('content')
    <x-admin.list-header :placeholder="__('admin.patients.search')" />

    <div class="admin-card overflow-hidden">
        @if ($patients->isEmpty())
            <x-admin.empty :message="__('admin.patients.empty')" icon="user-round" />
        @else
            <ul>
                @foreach ($patients as $patient)
                    <li class="admin-row flex flex-wrap items-center gap-4 px-5 py-4 first:border-0">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-navy-900 text-xs font-bold text-white">
                            {{ Str::upper(Str::substr($patient->name, 0, 2)) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-navy-900">
                                {{ $patient->name }}
                                @unless ($patient->is_active)
                                    <span class="ms-1 badge-slate">{{ __('admin.states.hidden') }}</span>
                                @endunless
                            </span>
                            <span class="block truncate text-xs text-navy-900/50">
                                <span class="font-mono">{{ $patient->displayPhone() }}</span>
                                @if ($patient->email)
                                    <span class="mx-1">·</span>{{ $patient->email }}
                                @endif
                            </span>
                            <span class="block truncate text-xs text-navy-900/40">
                                {{ __('admin.patients.registered_on', ['date' => $patient->created_at->translatedFormat('j M Y')]) }}
                                <span class="mx-1">·</span>
                                @if ($patient->last_login_at)
                                    {{ __('admin.patients.last_login', ['date' => $patient->last_login_at->diffForHumans()]) }}
                                @else
                                    {{ __('admin.patients.never_signed_in') }}
                                @endif
                            </span>
                        </span>

                        <a href="{{ route('admin.patients.documents', $patient) }}" class="btn-outline btn-sm">
                            {{ __('admin.patients.documents_link') }}
                            <span class="opacity-60">{{ $patient->documents_count }}</span>
                        </a>

                        <form method="POST" action="{{ route('admin.patients.toggle', $patient) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="{{ $patient->is_active ? 'btn-danger' : 'btn-outline' }} btn-sm">
                                {{ $patient->is_active ? __('admin.patients.disable') : __('admin.patients.enable') }}
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-5">{{ $patients->links() }}</div>
@endsection
