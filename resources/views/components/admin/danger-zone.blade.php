@props(['action', 'title' => null, 'description' => null, 'label' => null, 'confirm' => null])

<div {{ $attributes->merge(['class' => 'mt-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-urgent-100 bg-urgent-50/50 px-5 py-4']) }}>
    <div class="min-w-0">
        <p class="text-sm font-semibold text-navy-900">{{ $title ?? __('admin.actions.delete') }}</p>
        <p class="mt-0.5 text-xs text-navy-900/55">{{ $description ?? __('admin.actions.delete_help') }}</p>
    </div>

    <x-admin.delete-form :action="$action" :confirm="$confirm" :label="$label" />
</div>
