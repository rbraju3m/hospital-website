@extends('admin.layouts.app')

@section('title', __('admin.nav.settings'))
@section('heading', __('admin.nav.settings'))
@section('subheading', __('admin.settings.intro'))

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}"
      x-data="{ tab: '{{ config('app.fallback_locale') }}' }" class="max-w-3xl">
    @csrf
    @method('PUT')

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <x-admin.locale-tabs />
        <p class="text-xs text-navy-900/45">{{ __('admin.settings.locale_note') }}</p>
    </div>

    <div class="space-y-6">
        @foreach ($groups as $group => $settings)
            <x-admin.section :title="__('admin.settings.groups.'.$group)">
                <div class="grid gap-5">
                    @foreach ($settings as $setting)
                        @php
                            $key = $setting->key;
                            $label = __('admin.settings.keys.'.$key);
                            $label = $label === 'admin.settings.keys.'.$key ? Str::headline($key) : $label;
                            $translatable = in_array($key, \App\Models\Setting::TRANSLATABLE_KEYS, true);
                            $long = Str::length($setting->untranslated('value') ?? '') > 60;
                        @endphp

                        @if ($translatable)
                            @foreach (config('app.available_locales', []) as $code => $meta)
                                @php
                                    $isFallback = $code === config('app.fallback_locale');
                                    $field = $isFallback ? "values[{$key}]" : "translations[{$code}][{$key}]";
                                    $oldKey = $isFallback ? "values.{$key}" : "translations.{$code}.{$key}";
                                    $current = old($oldKey, $isFallback
                                        ? $setting->untranslated('value')
                                        : $setting->translation('value', $code));
                                @endphp

                                <div x-show="tab === '{{ $code }}'" x-cloak>
                                    <label for="s-{{ $key }}-{{ $code }}" class="label">
                                        {{ $label }}
                                        @unless ($isFallback)
                                            <span class="ms-1 text-xs font-normal text-navy-900/40">{{ $meta['native'] }}</span>
                                        @endunless
                                    </label>

                                    @if ($long)
                                        <textarea id="s-{{ $key }}-{{ $code }}" name="{{ $field }}" rows="2"
                                                  lang="{{ $code }}" class="input input-sm">{{ $current }}</textarea>
                                    @else
                                        <input id="s-{{ $key }}-{{ $code }}" name="{{ $field }}" type="text"
                                               value="{{ $current }}" lang="{{ $code }}" class="input input-sm">
                                    @endif

                                    @error($oldKey)
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        @else
                            {{-- Phone numbers, addresses-as-data and URLs read the
                                 same in every language, so they show once. --}}
                            <div>
                                <label for="s-{{ $key }}" class="label">
                                    {{ $label }}
                                    <span class="ms-1 text-xs font-normal text-navy-900/35">{{ __('admin.settings.all_languages') }}</span>
                                </label>

                                @if ($long)
                                    <textarea id="s-{{ $key }}" name="values[{{ $key }}]" rows="2"
                                              class="input input-sm">{{ old("values.{$key}", $setting->untranslated('value')) }}</textarea>
                                @else
                                    <input id="s-{{ $key }}" name="values[{{ $key }}]" type="text"
                                           value="{{ old("values.{$key}", $setting->untranslated('value')) }}" class="input input-sm">
                                @endif

                                @error("values.{$key}")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-admin.section>
        @endforeach
    </div>

    <x-admin.form-actions :cancel="route('admin.dashboard')" />
</form>
@endsection
