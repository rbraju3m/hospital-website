<footer class="mt-20 bg-navy-950 text-white/70">

    {{-- Emergency strip --}}
    <div class="border-b border-white/10 bg-urgent-600 text-white">
        <div class="shell flex flex-col items-center justify-between gap-4 py-5 sm:flex-row">
            <div class="flex items-center gap-3 text-center sm:text-left">
                <x-icon name="ambulance" size="28" stroke="1.6" class="hidden sm:block" />
                <div>
                    <p class="font-display text-lg font-bold text-white">{{ __('nav.footer.emergency_heading') }}</p>
                    <p class="text-sm text-white/85">{{ __('nav.footer.emergency_body') }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <a href="tel:{{ setting('hotline') }}"
                   class="btn bg-white px-6 py-3 text-urgent-700 hover:bg-white/90">
                    <x-icon name="phone" size="16" /> {{ __('nav.footer.call_number', ['number' => setting('hotline')]) }}
                </a>
                <a href="tel:{{ setting('ambulance_number') }}"
                   class="btn border border-white/40 px-6 py-3 text-white hover:bg-white/10">
                    <x-icon name="ambulance" size="16" /> {{ __('common.ambulance') }}
                </a>
            </div>
        </div>
    </div>

    <div class="shell grid gap-10 py-16 md:grid-cols-2 lg:grid-cols-12">

        {{-- Brand --}}
        <div class="lg:col-span-4">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-teal-600 text-white">
                    <x-icon name="heart-pulse" size="24" stroke="2" />
                </span>
                <span class="font-display text-xl font-extrabold tracking-tight text-white">{{ setting('site_name') }}</span>
            </div>

            <p class="mt-5 max-w-sm text-sm leading-relaxed">
                {{ __('nav.footer.blurb', [
                    'beds' => setting('bed_count'),
                    'year' => setting('established_year'),
                    'accreditation' => setting('accreditation'),
                ]) }}
            </p>

            <div class="mt-6 space-y-3 text-sm">
                <p class="flex items-start gap-3">
                    <x-icon name="map-pin" size="18" class="mt-0.5 text-teal-400" />
                    <span>{{ setting('address_line') }}<br>{{ setting('address_city') }}</span>
                </p>
                <p class="flex items-center gap-3">
                    <x-icon name="mail" size="18" class="text-teal-400" />
                    <a href="mailto:{{ setting('email') }}" class="transition hover:text-white">{{ setting('email') }}</a>
                </p>
                <p class="flex items-center gap-3">
                    <x-icon name="phone" size="18" class="text-teal-400" />
                    <a href="tel:{{ setting('appointment_number') }}" class="transition hover:text-white">
                        {{ __('nav.footer.appointments_suffix', ['number' => setting('appointment_number')]) }}
                    </a>
                </p>
            </div>

            <div class="mt-6 flex items-center gap-2">
                @foreach (['facebook' => 'facebook', 'youtube' => 'youtube', 'linkedin' => 'linkedin', 'instagram' => 'instagram'] as $key => $icon)
                    @if (setting($key))
                        <a href="{{ setting($key) }}" target="_blank" rel="noopener noreferrer"
                           aria-label="{{ ucfirst($key) }}"
                           class="grid h-9 w-9 place-items-center rounded-lg bg-white/10 text-white transition hover:bg-teal-600">
                            <x-icon :name="$icon" size="17" />
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Centres of excellence --}}
        <div class="lg:col-span-3">
            <h3 class="font-display text-sm font-bold uppercase tracking-[0.14em] text-white">{{ __('nav.footer.centres') }}</h3>
            <ul class="mt-5 space-y-2.5 text-sm">
                @foreach ($navDepartments->take(8) as $dept)
                    <li>
                        <a href="{{ route('departments.show', $dept) }}" class="transition hover:text-teal-300">{{ $dept->name }}</a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('departments.index') }}" class="font-semibold text-teal-400 transition hover:text-teal-300">
                        {{ __('nav.footer.all_departments') }}
                    </a>
                </li>
            </ul>
        </div>

        {{-- Patients --}}
        <div class="lg:col-span-2">
            <h3 class="font-display text-sm font-bold uppercase tracking-[0.14em] text-white">{{ __('nav.footer.for_patients') }}</h3>
            <ul class="mt-5 space-y-2.5 text-sm">
                @foreach ([
                    ['appointment.create', __('common.book_appointment_short')],
                    ['doctors.index', __('nav.items.doctors')],
                    ['packages.index', __('nav.items.packages')],
                    ['diagnostics.index', __('nav.items.diagnostics')],
                    ['portal.dashboard', __('nav.items.portal')],
                    ['services.index', __('nav.items.services')],
                    ['emergency', __('nav.footer.emergency_care')],
                    ['international', __('nav.items.international')],
                    ['posts.index', __('nav.items.posts')],
                ] as [$route, $label])
                    <li><a href="{{ route($route) }}" class="transition hover:text-teal-300">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Hospital --}}
        <div class="lg:col-span-3">
            <h3 class="font-display text-sm font-bold uppercase tracking-[0.14em] text-white">{{ __('nav.footer.hospital') }}</h3>
            <ul class="mt-5 space-y-2.5 text-sm">
                <li>
                    <a href="{{ route('about') }}" class="transition hover:text-teal-300">
                        {{ __('nav.footer.about_link', ['name' => setting('site_name')]) }}
                    </a>
                </li>
                <li><a href="{{ route('contact') }}" class="transition hover:text-teal-300">{{ __('nav.footer.contact_link') }}</a></li>
                <li><a href="{{ route('services.index') }}" class="transition hover:text-teal-300">{{ __('nav.footer.facilities_link') }}</a></li>
            </ul>

            <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-400">{{ __('nav.footer.hotline_label') }}</p>
                <a href="tel:{{ setting('hotline') }}"
                   class="mt-1 block font-display text-3xl font-extrabold text-white transition hover:text-teal-300">
                    {{ setting('hotline') }}
                </a>
                <p class="mt-2 text-xs">{{ setting('opening_hours') }}</p>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="shell flex flex-col items-center justify-between gap-3 py-6 text-xs sm:flex-row">
            <p>{{ __('nav.footer.copyright', ['year' => date('Y'), 'name' => setting('site_name')]) }}</p>
            <p class="text-white/45">{{ __('nav.footer.disclaimer') }}</p>
        </div>
    </div>
</footer>
