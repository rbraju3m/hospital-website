@props(['model'])

{{-- Which languages this row is complete in. Missing translations do not break
     a page — they fall back — so the panel has to make them visible. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    @foreach (translation_locales() as $locale)
        @php
            $missing = $model->missingTranslations($locale);
            $short = config("app.available_locales.{$locale}.short", Str::upper($locale));
        @endphp

        <span @class([
                  'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                  'bg-teal-50 text-teal-700' => $missing === [],
                  'bg-amber-50 text-amber-700' => $missing !== [],
              ])
              title="{{ $missing === []
                  ? __('admin.form.translated', ['locale' => $locale])
                  : __('admin.form.missing_fields', ['fields' => implode(', ', $missing)]) }}">
            {{ $short }}
        </span>
    @endforeach
</span>
