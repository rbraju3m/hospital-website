@extends('portal.layouts.app')

@section('title', __('portal.profile.title'))

@section('content')
<div class="max-w-2xl">
    <h1 class="font-display text-2xl font-bold text-navy-900">{{ __('portal.profile.title') }}</h1>
    <p class="mt-1.5 text-sm text-navy-900/55">{{ __('portal.profile.lede') }}</p>

    <form method="POST" action="{{ route('portal.profile.update') }}" class="mt-8 space-y-6">
        @csrf
        @method('PUT')

        <div class="card p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="label">{{ __('portal.fields.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $patient->name) }}" required
                           class="input @error('name') input-error @enderror">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <span class="label">{{ __('portal.fields.phone') }}</span>
                    <input type="text" value="{{ $patient->displayPhone() }}" disabled
                           class="input bg-mist-50 text-navy-900/55">
                    <p class="mt-1.5 text-xs text-navy-900/45">
                        {{ __('portal.profile.phone_locked', ['phone' => setting('hotline')]) }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="label">
                        {{ __('portal.fields.email') }}
                        <span class="font-normal text-navy-900/40">{{ __('common.optional') }}</span>
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email', $patient->email) }}"
                           class="input @error('email') input-error @enderror">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date_of_birth" class="label">{{ __('portal.fields.date_of_birth') }}</label>
                    <input id="date_of_birth" name="date_of_birth" type="date"
                           value="{{ old('date_of_birth', $patient->date_of_birth?->toDateString()) }}"
                           class="input @error('date_of_birth') input-error @enderror">
                    @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="gender" class="label">{{ __('portal.fields.gender') }}</label>
                    <select id="gender" name="gender" class="input">
                        <option value="">{{ __('admin.states.unspecified') }}</option>
                        @foreach (['male', 'female', 'other'] as $option)
                            <option value="{{ $option }}" @selected(old('gender', $patient->gender) === $option)>
                                {{ __("admin.gender.{$option}") }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-display text-base font-bold text-navy-900">{{ __('portal.profile.password_title') }}</h2>
            <p class="mt-1 text-xs text-navy-900/50">{{ __('portal.profile.password_hint') }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="current_password" class="label">{{ __('portal.fields.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="input @error('current_password') input-error @enderror">
                    @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="label">{{ __('portal.fields.password') }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           class="input @error('password') input-error @enderror">
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">{{ __('portal.fields.password_confirmation') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           autocomplete="new-password" class="input">
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary">{{ __('portal.profile.save') }}</button>
    </form>
</div>
@endsection
