@props(['action', 'confirm' => null, 'label' => null, 'compact' => false])

<form method="POST" action="{{ $action }}"
      data-confirm="{{ $confirm ?? __('admin.actions.confirm_delete') }}"
      {{ $attributes->merge(['class' => 'inline']) }}>
    @csrf
    @method('DELETE')

    @if ($compact)
        <button type="submit" title="{{ $label ?? __('admin.actions.delete') }}"
                class="rounded-lg p-2 text-navy-900/40 transition hover:bg-urgent-50 hover:text-urgent-700">
            <span class="sr-only">{{ $label ?? __('admin.actions.delete') }}</span>
            <x-icon name="trash" size="16" />
        </button>
    @else
        <button type="submit" class="btn-danger btn-sm">
            <x-icon name="trash" size="15" />
            {{ $label ?? __('admin.actions.delete') }}
        </button>
    @endif
</form>
