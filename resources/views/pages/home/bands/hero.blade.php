@php
    $heroImage = demo_image('hero', 'home');
    $showBooker = feature('home_booker');
@endphp

<section class="relative overflow-hidden bg-navy-900 dark:bg-navy-100">
    {{-- Photography carries the hero; the navy wash over it is what keeps the
         headline legible at every crop. Grid and orbs sit on top for depth.
         All of it is switched off under prefers-reduced-motion, and by the
         Site controls motion switch, by the global rule. --}}
    @if ($heroImage)
        <div aria-hidden="true" class="absolute inset-0">
            <img src="{{ $heroImage }}" alt=""
                 class="ken-burns h-full w-full object-cover object-[62%_center] opacity-[0.55]"
                 data-parallax="0.10">
            <div class="absolute inset-0 bg-gradient-to-r from-navy-950 dark:from-navy-50 via-navy-950/88 dark:via-navy-50/88 to-navy-950/25 dark:to-navy-50/25"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-navy-900 dark:from-navy-100 via-navy-900/10 dark:via-navy-100/10 to-navy-950/60 dark:to-navy-50/60"></div>
        </div>
    @endif

    <div aria-hidden="true" class="hero-grid opacity-[0.12]"></div>
    <div aria-hidden="true" class="orb -right-40 -top-40 h-[32rem] w-[32rem] bg-teal-500/25"></div>
    <div aria-hidden="true" class="orb -bottom-52 -left-40 h-[28rem] w-[28rem] bg-navy-400/20" style="--anim-delay:-6s"></div>

    <div class="shell relative grid items-center gap-14 py-16 lg:grid-cols-12 lg:py-24">

        <div class="{{ $showBooker ? 'lg:col-span-7' : 'lg:col-span-9' }}">
            <p class="eyebrow anim-fade-up text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ setting('accreditation') }}
            </p>

            <h1 class="h-display anim-fade-up mt-5 text-white" style="--anim-delay:90ms">
                {{ __('home.hero.heading_line_1') }}<br class="hidden sm:block">
                {{ __('home.hero.heading_line_2_before') }}
                <span class="text-teal-300">{{ __('home.hero.heading_line_2_accent') }}</span>
            </h1>

            <p class="lede anim-fade-up mt-6 max-w-xl text-white/70" style="--anim-delay:180ms">
                {{ __('home.hero.lede', [
                    'doctors' => setting('stat_doctors'),
                    'departments' => setting('stat_departments'),
                    'icu' => setting('stat_icu_beds'),
                    'city' => str(setting('address_city'))->before(','),
                ]) }}
            </p>

            <div class="anim-fade-up mt-9 flex flex-wrap items-center gap-3" style="--anim-delay:270ms">
                @if (feature('area_appointment'))
                    <a href="{{ route('appointment.create') }}" class="btn-accent btn-lg btn-nudge">
                        <x-icon name="calendar-check" size="18" />
                        {{ __('common.book_appointment') }}
                        <x-icon name="arrow-right" size="18" />
                    </a>
                @endif

                @if (feature('area_doctors'))
                    <a href="{{ route('doctors.index') }}"
                       class="btn btn-lg border border-white/25 text-white transition hover:border-white/50 hover:bg-white/10">
                        <x-icon name="search" size="18" />
                        {{ __('common.find_a_doctor') }}
                    </a>
                @endif

                <a href="tel:{{ setting('hotline') }}"
                   class="btn btn-lg text-white/80 transition hover:text-white">
                    <x-icon name="phone-call" size="18" />
                    {{ setting('hotline') }}
                </a>
            </div>

            @if (feature('home_stats'))
            <dl class="anim-fade-up mt-12 grid max-w-lg grid-cols-2 gap-x-8 gap-y-6 border-t border-white/10 pt-8 sm:grid-cols-4"
                style="--anim-delay:360ms">
                @foreach ([
                    ['stat_doctors', __('home.hero.stat_consultants'), '+'],
                    ['stat_beds', __('home.hero.stat_beds'), ''],
                    ['stat_departments', __('home.hero.stat_departments'), ''],
                    ['stat_years', __('home.hero.stat_years'), ''],
                ] as [$key, $label, $suffix])
                    <div>
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="font-display text-3xl font-extrabold text-white" data-countup>
                            {{ setting($key) }}{{ $suffix }}
                        </dd>
                        <p class="mt-1 text-xs font-medium tracking-wide text-white/50">{{ $label }}</p>
                    </div>
                @endforeach
            </dl>
            @endif
        </div>

        {{-- Quick appointment launcher --}}
        @if ($showBooker)
        <div class="lg:col-span-5">
            <div class="card anim-scale-in overflow-hidden p-0 shadow-lift" style="--anim-delay:220ms">
                <div class="border-b border-mist-200 bg-mist-50 px-7 py-5">
                    <h2 class="font-display text-lg font-bold text-navy-900">{{ __('home.booker.heading') }}</h2>
                    <p class="mt-1 text-sm text-navy-900/55">{{ __('home.booker.lede') }}</p>
                </div>

                <form action="{{ route('appointment.create') }}" method="GET" class="space-y-4 p-7">
                    <div>
                        <label for="hero-department" class="label">{{ __('home.booker.department') }}</label>
                        <select id="hero-department" name="department" class="input">
                            <option value="">{{ __('common.any_department') }}</option>
                            @foreach ($departmentOptions as $dept)
                                <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        {{ __('home.booker.continue') }}
                        <x-icon name="arrow-right" size="16" />
                    </button>

                    <p class="text-center text-xs text-navy-900/45">
                        {{ __('home.booker.prefer_to_talk') }}
                        <a href="tel:{{ setting('appointment_number') }}" class="font-semibold text-teal-700 hover:underline">
                            {{ setting('appointment_number') }}
                        </a>
                    </p>
                </form>

                <div class="flex items-center gap-3 border-t border-mist-200 bg-urgent-50 px-7 py-4">
                    <x-icon name="ambulance" size="22" class="shrink-0 text-urgent-600" />
                    <p class="text-sm text-navy-900/70">
                        <span class="font-semibold text-urgent-700">{{ __('home.booker.emergency_label') }}</span>
                        {{ __('home.booker.emergency_body') }}
                        <a href="tel:{{ setting('hotline') }}" class="font-bold text-urgent-700 hover:underline">{{ setting('hotline') }}</a>.
                        {{ __('home.booker.emergency_suffix') }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
