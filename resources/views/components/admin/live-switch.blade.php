@props(['model', 'label' => null])

{{-- Publish or hide without opening the record. The hidden input sits
     immediately before the track, which is what lets the checked state be plain
     CSS rather than a peer variant reaching across nesting levels. --}}
<label class="switch-sm" title="{{ $label ?? __('admin.lists.toggle_help') }}">
    <span class="sr-only">{{ $label ?? __('admin.lists.toggle_help') }}</span>
    <input type="checkbox" @checked($model->is_active)
           @change="toggle({{ $model->id }}, $event)"
           class="peer sr-only">
    <span class="switch-track" aria-hidden="true"></span>
</label>
