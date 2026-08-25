@if (feature('home_quick_actions'))
@php
    // Filtered against Site controls, then laid out on the count that survived
    // — five tiles across six columns would leave a hole in the row.
    $quickActions = collect([
        ['emergency', 'ambulance', __('home.quick.emergency'), __('home.quick.emergency_sub'), 'urgent', 'area_emergency'],
        ['doctors.index', 'user-round', __('home.quick.doctors'), __('home.quick.doctors_sub', ['count' => setting('stat_doctors')]), 'teal', 'area_doctors'],
        ['appointment.create', 'calendar-check', __('home.quick.appointment'), __('home.quick.appointment_sub'), 'teal', 'area_appointment'],
        ['departments.index', 'building', __('home.quick.departments'), __('home.quick.departments_sub', ['count' => setting('stat_departments')]), 'navy', 'area_departments'],
        ['diagnostics.index', 'microscope', __('home.quick.diagnostics'), __('home.quick.diagnostics_sub'), 'navy', 'area_diagnostics'],
        ['packages.index', 'check-circle', __('home.quick.checks'), __('home.quick.checks_sub', ['price' => number_format($cheapestPackage)]), 'navy', 'area_packages'],
    ])->filter(fn ($tile) => feature($tile[5]))->values();
@endphp

@if ($quickActions->isNotEmpty())
<section class="relative z-10 -mt-8 lg:-mt-10">
    <div class="shell">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3
                    lg:[grid-template-columns:repeat(var(--quick-cols),minmax(0,1fr))]"
             style="--quick-cols: {{ $quickActions->count() }}"
             data-reveal-stagger="60">
            @foreach ($quickActions as [$route, $icon, $label, $sub, $tone, $flag])
                <a href="{{ route($route) }}"
                   class="card-interactive reveal group flex flex-col items-center gap-2 p-5 text-center">
                    <span @class([
                        'grid h-11 w-11 place-items-center rounded-xl transition duration-300 ease-out group-hover:scale-110',
                        'bg-urgent-50 text-urgent-600 group-hover:bg-urgent-600 group-hover:text-white' => $tone === 'urgent',
                        'bg-teal-50 text-teal-700 group-hover:bg-teal-600 group-hover:text-white' => $tone === 'teal',
                        'bg-navy-50 text-navy-700 group-hover:bg-navy-900 group-hover:dark:bg-navy-100 group-hover:text-white' => $tone === 'navy',
                    ])>
                        <x-icon :name="$icon" size="21" />
                    </span>
                    <span class="text-sm font-semibold text-navy-900 transition group-hover:text-teal-700">{{ $label }}</span>
                    <span class="text-[11px] text-navy-900/50">{{ $sub }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endif
