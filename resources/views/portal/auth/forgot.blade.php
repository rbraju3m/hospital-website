@extends('portal.layouts.guest')

@section('title', __('portal.forgot.title'))
@section('heading', __('portal.forgot.title'))
@section('lede', __('portal.forgot.lede'))

@section('form')
<form method="POST" action="{{ route('portal.password.send') }}" class="mt-6 space-y-5">
    @csrf

    <div>
        <label for="phone" class="label">{{ __('portal.fields.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autofocus
               inputmode="numeric" placeholder="01XXXXXXXXX"
               class="input @error('phone') input-error @enderror">
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="btn-primary w-full">{{ __('portal.forgot.submit') }}</button>
</form>

<p class="mt-6 text-center text-sm">
    <a href="{{ route('portal.login') }}" class="font-medium text-navy-900/55 hover:text-navy-900">
        ← {{ __('portal.forgot.back') }}
    </a>
</p>
@endsection
