@extends('portal.layouts.guest')

@section('title', __('portal.register.title'))
@section('heading', __('portal.register.title'))
@section('lede', __('portal.register.lede'))

@section('form')
<form method="POST" action="{{ route('portal.register.store') }}" class="mt-6 space-y-5">
    @csrf

    <div>
        <label for="name" class="label">{{ __('portal.fields.name') }}</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
               autocomplete="name" class="input @error('name') input-error @enderror">
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="label">{{ __('portal.fields.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
               inputmode="numeric" autocomplete="username" placeholder="01XXXXXXXXX"
               class="input @error('phone') input-error @enderror @error('phone_national') input-error @enderror">
        <p class="mt-1.5 text-xs text-navy-900/45">{{ __('portal.register.phone_help') }}</p>
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
        @error('phone_national') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="label">
            {{ __('portal.fields.email') }}
            <span class="font-normal text-navy-900/40">{{ __('common.optional') }}</span>
        </label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
               class="input @error('email') input-error @enderror">
        <p class="mt-1.5 text-xs text-navy-900/45">{{ __('portal.register.email_help') }}</p>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
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

    <button type="submit" class="btn-primary w-full">{{ __('portal.register.submit') }}</button>
</form>

<p class="mt-6 border-t border-mist-200 pt-5 text-center text-sm text-navy-900/60">
    {{ __('portal.register.have_account') }}
    <a href="{{ route('portal.login') }}" class="font-semibold text-teal-700 hover:text-teal-800">
        {{ __('portal.register.login_link') }}
    </a>
</p>
@endsection
