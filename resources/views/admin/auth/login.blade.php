<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('admin.auth.sign_in') }} — {{ __('admin.panel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-navy-950 px-5 py-12">

<div class="w-full max-w-md">
    <div class="mb-8 flex items-center justify-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-teal-500 font-display text-sm font-extrabold text-navy-950">
            RBR
        </span>
        <span class="font-display text-lg font-bold text-white">{{ setting('site_name', 'RBR Hospital') }}</span>
    </div>

    <div class="rounded-[1.25rem] bg-white p-8 shadow-lift">
        <h1 class="font-display text-xl font-bold text-navy-900">{{ __('admin.auth.sign_in') }}</h1>
        <p class="mt-1.5 text-sm text-navy-900/55">{{ __('admin.auth.intro') }}</p>

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">
            @csrf

            <div>
                <label for="email" class="label">{{ __('admin.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       autocomplete="username" required autofocus
                       class="input @error('email') input-error @enderror">
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">{{ __('admin.auth.password') }}</label>
                <input id="password" name="password" type="password"
                       autocomplete="current-password" required
                       class="input @error('password') input-error @enderror">
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2.5 text-sm text-navy-900/70">
                <input type="checkbox" name="remember" value="1"
                       class="h-4 w-4 rounded border-mist-200 text-teal-600 focus:ring-teal-500/30">
                {{ __('admin.auth.remember') }}
            </label>

            <button type="submit" class="btn-primary w-full">{{ __('admin.auth.sign_in') }}</button>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-white/40">
        <a href="{{ route('home') }}" class="hover:text-white/70">← {{ __('admin.auth.back_to_site') }}</a>
    </p>
</div>

</body>
</html>
