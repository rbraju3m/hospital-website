@props(['cancel', 'submit' => null])

{{-- Destructive actions deliberately live outside the edit form: a delete needs
     its own <form>, and HTML does not allow one form inside another. --}}
<div {{ $attributes->merge(['class' => 'sticky bottom-0 -mx-5 mt-6 flex flex-wrap items-center gap-3 border-t border-mist-200 bg-white/95 dark:bg-navy-100/95 px-5 py-4 backdrop-blur sm:-mx-8 sm:px-8 lg:-mx-10 lg:px-10']) }}>
    <button type="submit" class="btn-primary btn-sm">
        <x-icon name="check" size="15" stroke="2.5" />
        {{ $submit ?? __('admin.actions.save') }}
    </button>

    <a href="{{ $cancel }}" class="btn-ghost btn-sm">{{ __('admin.actions.cancel') }}</a>
</div>
