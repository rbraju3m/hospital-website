@php $locales = config('app.available_locales', []); @endphp

{{-- One switch for the whole form: every translatable field follows `tab`,
     so an editor moves between languages once rather than field by field. --}}
@if (count($locales) > 1)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-xl bg-mist-100 p-1']) }}>
        <span class="sr-only">{{ __('admin.form.editing_language') }}</span>
        @foreach ($locales as $code => $meta)
            <button type="button" @click="tab = '{{ $code }}'"
                    :class="tab === '{{ $code }}' ? 'locale-tab-active' : 'locale-tab'"
                    :aria-pressed="tab === '{{ $code }}'">
                {{ $meta['native'] }}
            </button>
        @endforeach
    </div>
@endif
