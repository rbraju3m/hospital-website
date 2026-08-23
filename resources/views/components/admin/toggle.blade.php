@props(['name', 'label', 'value' => false, 'help' => null])

{{-- The hidden 0 makes an unchecked box post a value; without it "unpublish"
     would silently do nothing, because browsers omit unchecked checkboxes. --}}
<label {{ $attributes->merge(['class' => 'flex cursor-pointer items-start gap-3 rounded-xl border border-mist-200 px-4 py-3 transition hover:border-navy-200']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $value))
           class="mt-0.5 h-4 w-4 shrink-0 rounded border-mist-200 text-teal-600 focus:ring-teal-500/30">
    <span class="min-w-0">
        <span class="block text-sm font-medium text-navy-900">{{ $label }}</span>
        @if ($help)
            <span class="mt-0.5 block text-xs text-navy-900/45">{{ $help }}</span>
        @endif
    </span>
</label>
