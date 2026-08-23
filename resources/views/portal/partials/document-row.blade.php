@props(['document'])

<div class="flex flex-wrap items-center gap-4 border-t border-mist-200 px-5 py-4 first:border-0">
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-mist-100 text-navy-900/50">
        <x-icon name="file-text" size="18" />
    </span>

    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-navy-900">{{ $document->title }}</p>
        <p class="truncate text-xs text-navy-900/50">
            <span class="chip !py-0.5 !text-[11px]">{{ __("portal.categories.{$document->category}") }}</span>
            <span class="ms-1">
                @if ($document->issued_at)
                    {{ __('portal.documents.issued', ['date' => $document->issued_at->translatedFormat('j M Y')]) }}
                @else
                    {{ __('portal.documents.filed', ['date' => $document->created_at->translatedFormat('j M Y')]) }}
                @endif
            </span>
            <span class="ms-1">· {{ $document->readableSize() }}</span>
        </p>
    </div>

    <a href="{{ route('portal.documents.download', $document) }}" class="btn-outline btn-sm shrink-0">
        <x-icon name="download" size="15" />
        {{ __('portal.documents.download') }}
    </a>
</div>
