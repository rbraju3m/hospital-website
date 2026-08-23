{{-- Persistent thumb-reach actions on mobile. Hidden from lg upward, where the
     header already exposes the same actions. --}}
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-mist-200 bg-white/95 backdrop-blur lg:hidden">
    <div class="grid grid-cols-4">
        <a href="tel:{{ setting('ambulance_number') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-urgent-600">
            <x-icon name="ambulance" size="20" />
            {{ __('nav.mobile.emergency') }}
        </a>
        <a href="{{ route('doctors.index') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-navy-900/70">
            <x-icon name="search" size="20" />
            {{ __('nav.mobile.doctors') }}
        </a>
        <a href="tel:{{ setting('hotline') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-navy-900/70">
            <x-icon name="phone" size="20" />
            {{ __('nav.mobile.call') }}
        </a>
        <a href="{{ route('appointment.create') }}"
           class="flex flex-col items-center gap-1 bg-teal-600 py-2.5 text-[11px] font-semibold text-white">
            <x-icon name="calendar-check" size="20" />
            {{ __('nav.mobile.book') }}
        </a>
    </div>
</div>
