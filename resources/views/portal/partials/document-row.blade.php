@props(['document'])

<div class="group flex flex-wrap items-center gap-4 border-t border-mist-200 px-5 py-4
            transition duration-200 ease-out first:border-0 hover:bg-mist-50">
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-mist-100 text-navy-900/50
                 transition duration-300 ease-out group-hover:scale-105 group-hover:bg-teal-50 group-hover:text-teal-700">
        <x-icon name="file-text" size="18" />
    </span>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <p class="truncate font-semibold text-navy-900">{{ $document->title }}</p>
            @if ($document->category === 'bill' && $document->amount)
                <span class="shrink-0 text-sm font-bold text-teal-700">
                    ৳{{ number_format($document->amount) }}
                </span>
                @if ($document->payment_status === 'paid')
                    <span class="chip badge-teal !py-0.5 !text-[11px]">
                        {{ __('portal.payments.paid') }}
                    </span>
                @elseif ($document->payment_status === 'unpaid')
                    <span class="chip badge-amber !py-0.5 !text-[11px]">
                        {{ __('portal.payments.unpaid') }}
                    </span>
                @endif
            @endif
        </div>
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

    <div class="flex gap-2 shrink-0">
        @if ($document->category === 'bill' && $document->payment_status === 'unpaid')
            <form method="POST" action="{{ route('portal.payments.initiate', $document) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <x-icon name="credit-card" size="15" />
                    {{ __('portal.payments.pay_now', ['amount' => number_format($document->amount)]) }}
                </button>
            </form>
        @endif
        <a href="{{ route('portal.documents.download', $document) }}" class="btn-outline btn-sm">
            <x-icon name="download" size="15" />
            {{ __('portal.documents.download') }}
        </a>
    </div>
</div>
