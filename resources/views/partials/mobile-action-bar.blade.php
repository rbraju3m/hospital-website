@php
    /* Persistent thumb-reach actions on mobile. Hidden from lg upward, where the
       header already exposes the same actions.

       The two phone numbers are unconditional — they are the reason this bar
       exists. The two page links come and go with Site controls, and the grid
       counts what survived rather than leaving a gap where a link used to be. */
    $actions = collect([
        [
            'href' => 'tel:'.setting('ambulance_number'),
            'icon' => 'ambulance',
            'label' => __('nav.mobile.emergency'),
            'tone' => 'urgent',
            'show' => true,
            'active' => false,
        ],
        [
            'href' => feature('area_doctors') ? route('doctors.index') : null,
            'icon' => 'search',
            'label' => __('nav.mobile.doctors'),
            'tone' => 'plain',
            'show' => feature('area_doctors'),
            'active' => request()->routeIs('doctors.*'),
        ],
        [
            'href' => 'tel:'.setting('hotline'),
            'icon' => 'phone',
            'label' => __('nav.mobile.call'),
            'tone' => 'plain',
            'show' => true,
            'active' => false,
        ],
        [
            'href' => feature('area_appointment') ? route('appointment.create') : null,
            'icon' => 'calendar-check',
            'label' => __('nav.mobile.book'),
            'tone' => 'accent',
            'show' => feature('area_appointment'),
            'active' => false,
        ],
    ])->where('show', true)->values();
@endphp

<div class="fixed inset-x-0 bottom-0 z-40 border-t border-mist-200 bg-white/90 backdrop-blur-xl
            shadow-[0_-8px_24px_-16px_rgb(11_44_77/0.35)] lg:hidden">
    <div class="grid" style="grid-template-columns: repeat({{ $actions->count() }}, minmax(0, 1fr))">
        @foreach ($actions as $action)
            <a href="{{ $action['href'] }}"
               @class([
                   'relative flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold transition duration-200 active:scale-95',
                   'text-urgent-600 active:bg-urgent-50' => $action['tone'] === 'urgent',
                   'overflow-hidden bg-teal-600 text-white active:bg-teal-700' => $action['tone'] === 'accent',
                   'text-teal-700' => $action['tone'] === 'plain' && $action['active'],
                   'text-navy-900/70 active:bg-mist-50' => $action['tone'] === 'plain' && ! $action['active'],
               ])>
                <x-icon :name="$action['icon']" size="20" />
                {{ $action['label'] }}

                @if ($action['active'])
                    <span aria-hidden="true"
                          class="absolute inset-x-6 top-0 h-0.5 rounded-full bg-teal-600"></span>
                @endif
            </a>
        @endforeach
    </div>
</div>
