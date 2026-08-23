@props(['name', 'label' => null, 'options' => [], 'value' => null, 'help' => null, 'required' => false, 'placeholder' => null])

<x-admin.field :name="$name" :label="$label" :help="$help" :required="$required" :class="$attributes->get('class')">
    <select id="{{ $name }}" name="{{ $name }}"
            @if ($required) required @endif
            {{ $attributes->except('class')->merge(['class' => 'input input-sm '.($errors->has($name) ? 'input-error' : '')]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</x-admin.field>
