@extends('portal.layouts.guest')

@section('title', __('portal.reset.title'))
@section('heading', __('portal.reset.title'))
@section('lede', __('portal.reset.lede'))

@section('form')
<form method="POST" action="{{ route('portal.password.update') }}" class="mt-6 space-y-5">
    @csrf

    <div>
        <label for="phone" class="label">{{ __('portal.fields.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $phone) }}" required
               inputmode="numeric" class="input @error('phone') input-error @enderror">
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="code" class="label">{{ __('portal.fields.code') }}</label>
        <input id="code" name="code" type="text" required autofocus inputmode="numeric"
               maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code"
               class="input text-center font-mono text-xl tracking-[0.5em] @error('code') input-error @enderror">
        @error('code') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="label">{{ __('portal.fields.password') }}</label>
        <input id="password" name="password" type="password" required autocomplete="new-password"
               class="input @error('password') input-error @enderror">
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="label">{{ __('portal.fields.password_confirmation') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
               autocomplete="new-password" class="input">
    </div>

    <button type="submit" class="btn-primary w-full">{{ __('portal.reset.submit') }}</button>
</form>

<p class="mt-6 text-center text-sm">
    <a href="{{ route('portal.password.request') }}" class="font-medium text-navy-900/55 hover:text-navy-900">
        {{ __('portal.reset.resend') }}
    </a>
</p>
@endsection
