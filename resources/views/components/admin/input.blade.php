@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'placeholder' => null])

<x-admin.field :name="$name" :label="$label" :help="$help" :required="$required" :class="$attributes->get('class')">
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ old($name, $value) }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @if ($required) required @endif
           {{ $attributes->except('class')->merge(['class' => 'input input-sm '.($errors->has($name) ? 'input-error' : '')]) }}>
</x-admin.field>
