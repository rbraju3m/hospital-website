@props(['name', 'label', 'value' => null, 'help' => null, 'aspect' => 'aspect-[4/3]'])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <span class="label">{{ $label }}</span>

    <div class="flex items-start gap-4">
        <span class="{{ $aspect }} w-28 shrink-0 overflow-hidden rounded-xl border border-mist-200 bg-mist-50">
            @if ($value)
                <img src="{{ media_url($value) }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="grid h-full w-full place-items-center text-navy-900/25">
                    <x-icon name="image" size="20" />
                </span>
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/*"
                   class="block w-full text-sm text-navy-900/70
                          file:me-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-4 file:py-2
                          file:text-sm file:font-semibold file:text-white hover:file:bg-navy-800">

            <p class="mt-1.5 text-xs text-navy-900/45">
                {{ $help ?? __('admin.form.image_help', ['formats' => 'JPG, PNG, WebP', 'size' => 4]) }}
            </p>

            @if ($value)
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
