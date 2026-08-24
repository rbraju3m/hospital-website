@props(['variant' => 'bar'])

{{-- Light / dark, remembered per browser. Until somebody chooses, the device
     decides — which is why the button reads its state from <html> rather than
     from a server-rendered value: the theme is settled by the inline script in
     the head, before first paint, and the server has no idea what it picked. --}}
<button type="button" x-data="themeToggle" @click="toggle()"
        :aria-pressed="dark"
        :title="dark ? @js(__('common.theme.light')) : @js(__('common.theme.dark'))"
        {{ $attributes->merge(['class' => match ($variant) {
            'drawer' => 'flex w-full items-center justify-center gap-2 rounded-xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-navy-900 transition duration-200 hover:border-teal-300 hover:bg-teal-50/60',
            'panel' => 'grid h-9 w-9 place-items-center rounded-xl text-navy-900/55 transition duration-200 hover:bg-mist-100 hover:text-navy-900',
            default => 'flex items-center gap-2 text-white/65 transition duration-200 hover:text-white',
        }]) }}>
    <span class="sr-only">{{ __('common.theme.toggle') }}</span>

    {{-- Both icons ship; the swap is a class, so it survives the page being
         restored from the back/forward cache in the other theme. --}}
    <span x-show="! dark" class="flex items-center gap-2">
        <x-icon name="moon" size="{{ $variant === 'bar' ? 15 : 17 }}" />
        @if ($variant === 'drawer') {{ __('common.theme.dark') }} @endif
    </span>
    <span x-show="dark" x-cloak class="flex items-center gap-2">
        <x-icon name="sun" size="{{ $variant === 'bar' ? 15 : 17 }}" />
        @if ($variant === 'drawer') {{ __('common.theme.light') }} @endif
    </span>
</button>
