@extends('portal.layouts.guest')

@section('title', __('portal.login.title'))
@section('heading', __('portal.login.title'))
@section('lede', __('portal.login.lede'))

@section('form')
<form method="POST" action="{{ route('portal.login.store') }}" class="mt-6 space-y-5">
    @csrf

    <div>
        <label for="phone" class="label">{{ __('portal.fields.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autofocus
               inputmode="numeric" autocomplete="username" placeholder="01XXXXXXXXX"
               class="input @error('phone') input-error @enderror">
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="label">{{ __('portal.fields.password') }}</label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
               class="input @error('password') input-error @enderror">
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <label class="flex items-center gap-2.5 text-sm text-navy-900/70">
            <input type="checkbox" name="remember" value="1"
                   class="h-4 w-4 rounded border-mist-200 text-teal-600 focus:ring-teal-500/30">
            {{ __('portal.login.remember') }}
        </label>

        <a href="{{ route('portal.password.request') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">
            {{ __('portal.login.forgot') }}
        </a>
    </div>

    <button type="submit" class="btn-primary w-full">{{ __('portal.login.submit') }}</button>
</form>

@if (feature('behaviour_portal_registration'))
    <p class="mt-6 border-t border-mist-200 pt-5 text-center text-sm text-navy-900/60">
        {{ __('portal.login.no_account') }}
        <a href="{{ route('portal.register') }}" class="font-semibold text-teal-700 hover:text-teal-800">
            {{ __('portal.login.register_link') }}
        </a>
    </p>
@endif
@endsection
