@props(['name', 'label' => null, 'help' => null, 'required' => false, 'errorKey' => null])

@php $key = $errorKey ?? $name; @endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @if ($label)
        <label for="{{ $name }}" class="label">
            {{ $label }}
            @if ($required)
                <span class="text-urgent-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($help)
        <p class="mt-1.5 text-xs text-navy-900/45">{{ $help }}</p>
    @endif

    @error($key)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
