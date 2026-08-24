@props(['model', 'compact' => false])

@php
    $locales = collect(translation_locales())
        ->map(fn ($locale) => [
            'code' => $locale,
            'missing' => $model->missingTranslations($locale),
            'short' => config("app.available_locales.{$locale}.short", Str::upper($locale)),
        ]);

    // Compact is for a listing, where a chip per language on every row is a
    // column's worth of noise saying "fine, fine, fine". Only a gap is news.
    $shown = $compact ? $locales->filter(fn ($l) => $l['missing'] !== []) : $locales;
@endphp

@if ($shown->isNotEmpty())
    {{-- Missing translations do not break a page — they fall back — so the
         panel has to make them visible. --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
        @foreach ($shown as $locale)
            <span @class([
                      'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                      'bg-teal-50 text-teal-700' => $locale['missing'] === [],
                      'bg-amber-50 text-amber-700' => $locale['missing'] !== [],
                  ])
                  title="{{ $locale['missing'] === []
                      ? __('admin.form.translated', ['locale' => $locale['code']])
                      : __('admin.form.missing_fields', ['fields' => implode(', ', $locale['missing'])]) }}">
                {{ $locale['short'] }}
            </span>
        @endforeach
    </span>
@endif
