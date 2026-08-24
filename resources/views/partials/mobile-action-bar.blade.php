{{-- Persistent thumb-reach actions on mobile. Hidden from lg upward, where the
     header already exposes the same actions. --}}
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-mist-200 bg-white/90 backdrop-blur-xl
            shadow-[0_-8px_24px_-16px_rgb(11_44_77/0.35)] lg:hidden">
    <div class="grid grid-cols-4">
        <a href="tel:{{ setting('ambulance_number') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-urgent-600
                  transition duration-200 active:scale-95 active:bg-urgent-50">
            <x-icon name="ambulance" size="20" />
            {{ __('nav.mobile.emergency') }}
        </a>
        <a href="{{ route('doctors.index') }}"
           @class([
               'flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold transition duration-200 active:scale-95',
               'text-teal-700' => request()->routeIs('doctors.*'),
               'text-navy-900/70' => ! request()->routeIs('doctors.*'),
           ])>
            <x-icon name="search" size="20" />
            {{ __('nav.mobile.doctors') }}
        </a>
        <a href="tel:{{ setting('hotline') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-navy-900/70
                  transition duration-200 active:scale-95 active:bg-mist-50">
            <x-icon name="phone" size="20" />
            {{ __('nav.mobile.call') }}
        </a>
        <a href="{{ route('appointment.create') }}"
           class="relative flex flex-col items-center gap-1 overflow-hidden bg-teal-600 py-2.5 text-[11px]
                  font-semibold text-white transition duration-200 active:scale-95 active:bg-teal-700">
            <x-icon name="calendar-check" size="20" />
            {{ __('nav.mobile.book') }}
        </a>
    </div>
</div>
