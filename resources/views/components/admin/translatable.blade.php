@props([
    'name',
    'label',
    'model' => null,
    'type' => 'text',   // text | textarea | list
    'rows' => 4,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $locales = config('app.available_locales', []);
    $fallback = config('app.fallback_locale');
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @foreach ($locales as $code => $meta)
        @php
            // The fallback locale lives in the ordinary column; every other
            // locale posts into translations[<locale>][<column>].
            $isFallback = $code === $fallback;
            $field = $isFallback ? $name : "translations[{$code}][{$name}]";
            $oldKey = $isFallback ? $name : "translations.{$code}.{$name}";
            $stored = $isFallback ? $model?->untranslated($name) : $model?->translation($name, $code);
            $current = old($oldKey, $type === 'list' ? array_to_lines($stored) : $stored);
            $id = 'f-'.$name.'-'.$code;
        @endphp

        <div x-show="tab === '{{ $code }}'" x-cloak>
            <label for="{{ $id }}" class="label">
                {{ $label }}
                @if ($required && $isFallback)
                    <span class="text-urgent-600" aria-hidden="true">*</span>
                @endif
                @unless ($isFallback)
                    <span class="ms-1 text-xs font-normal text-navy-900/40">{{ $meta['native'] }}</span>
                @endunless
            </label>

            @if ($type === 'text')
                <input id="{{ $id }}" name="{{ $field }}" type="text" value="{{ $current }}"
                       @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                       class="input input-sm @error($oldKey) input-error @enderror" lang="{{ $code }}">
            @else
                <textarea id="{{ $id }}" name="{{ $field }}" rows="{{ $type === 'list' ? min($rows, 6) : $rows }}"
                          @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                          class="input input-sm leading-relaxed @error($oldKey) input-error @enderror"
                          lang="{{ $code }}">{{ $current }}</textarea>
            @endif

            @if ($help)
                <p class="mt-1.5 text-xs text-navy-900/45">{{ $help }}</p>
            @endif

            @if ($type === 'list')
                <p class="mt-1.5 text-xs text-navy-900/45">{{ __('admin.form.one_per_line') }}</p>
            @endif

            @error($oldKey)
                <p class="field-error">{{ $message }}</p>
            @enderror

            {{-- A translation left blank falls back to the source language, so
                 say so rather than letting it read as a missing page. --}}
            @if (! $isFallback && blank($current))
                <p class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-700">
                    <x-icon name="languages" size="13" />
                    {{ __('admin.form.falls_back') }}
                </p>
            @endif
        </div>
    @endforeach
</div>
