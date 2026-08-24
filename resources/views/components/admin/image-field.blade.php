@props([
    'name',
    'label',
    'value' => null,
    'help' => null,
    'aspect' => 'aspect-[4/3]',
    'set' => null,
    'seed' => null,
    'group' => '',
    'preview' => null,
])

@php
    // What the public site will actually render for this row: the upload if
    // there is one, otherwise the stand-in it falls back to. Showing the
    // stand-in here — labelled as one — is the difference between "this row
    // has no picture" and "this row has a picture nobody chose".
    $uploaded = media_url($value);
    $preview ??= $set ? demo_image($set, $seed, $group) : null;
    $preview = $uploaded ?? $preview;
@endphp

<div {{ $attributes->merge(['class' => '@container min-w-0']) }}>
    <span class="label">{{ $label }}</span>

    {{-- The field sits in a wide form column on one page and in the ~21rem aside
         on another, so it measures its own box rather than the viewport: side by
         side where there is room, stacked where there is not. --}}
    <div class="flex flex-col gap-3 @xs:flex-row @xs:items-start @xs:gap-4">
        <span class="{{ $aspect }} relative w-28 shrink-0 overflow-hidden rounded-xl border border-mist-200 bg-mist-50">
            @if ($preview)
                <img src="{{ $preview }}" alt="" class="h-full w-full object-cover">

                @unless ($uploaded)
                    <span class="absolute inset-x-0 bottom-0 bg-navy-950/70 dark:bg-navy-50/70 py-1 text-center text-[10px]
                                 font-semibold uppercase tracking-wide text-white">
                        {{ __('admin.form.stand_in') }}
                    </span>
                @endunless
            @else
                <span class="grid h-full w-full place-items-center text-navy-900/25">
                    <x-icon name="image" size="20" />
                </span>
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/*"
                   data-compress
                   data-compress-busy="{{ __('admin.form.optimising') }}"
                   data-compress-done="{{ __('admin.form.optimised') }}"
                   class="block w-full text-sm text-navy-900/70
                          file:me-3 file:rounded-lg file:border-0 file:bg-navy-900 file:dark:bg-navy-100 file:px-4 file:py-2
                          file:text-sm file:font-semibold file:text-white file:transition file:duration-200
                          hover:file:bg-teal-600">

            <p class="mt-1.5 text-xs text-navy-900/45">
                {{ $help ?? __('admin.form.image_help', ['formats' => 'JPG, PNG, WebP', 'size' => round(\App\Services\MediaLibrary::maxKilobytes() / 1024, 1)]) }}
            </p>

            @if (! $uploaded && $preview)
                <p class="mt-1 text-xs text-navy-900/45">{{ __('admin.form.stand_in_help') }}</p>
            @endif

            @if ($uploaded)
                <label class="mt-2.5 flex items-center gap-2 text-xs text-navy-900/60">
                    <input type="checkbox" name="{{ $name }}_remove" value="1"
                           class="h-3.5 w-3.5 rounded border-mist-200 text-urgent-600 focus:ring-urgent-500/30">
                    {{ __('admin.form.remove_image') }}
                </label>
            @endif

            @error($name)
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
