{{-- What just happened, from the listing's own Alpine scope. A drag has already
     moved the row on screen, so a failure has to say so out loud. --}}
<div x-show="status" x-cloak
     x-transition:enter="transition duration-200 ease-out"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition duration-150 ease-in"
     x-transition:leave-end="opacity-0"
     :class="statusTone === 'error' ? 'bg-urgent-600' : 'bg-navy-900 dark:bg-navy-200'"
     class="fixed bottom-6 end-6 z-50 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm
            font-semibold text-white shadow-lift"
     role="status" aria-live="polite">
    <x-icon name="check" size="15" stroke="2.5" x-show="statusTone !== 'error'" />
    <x-icon name="alert-triangle" size="15" x-show="statusTone === 'error'" x-cloak />
    <span x-text="status"></span>
</div>
