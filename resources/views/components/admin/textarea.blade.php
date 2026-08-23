@props(['name', 'label' => null, 'value' => null, 'rows' => 4, 'help' => null, 'required' => false, 'placeholder' => null])

<x-admin.field :name="$name" :label="$label" :help="$help" :required="$required" :class="$attributes->get('class')">
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($required) required @endif
              {{ $attributes->except('class')->merge(['class' => 'input input-sm leading-relaxed '.($errors->has($name) ? 'input-error' : '')]) }}>{{ old($name, $value) }}</textarea>
</x-admin.field>
