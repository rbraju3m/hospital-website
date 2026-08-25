@if (feature('home_visit'))
<section class="pb-20">
    <div class="shell">
        <div class="reveal overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-navy-900 dark:from-navy-100 to-navy-950 dark:to-navy-50 text-white">
            <div class="grid gap-10 p-9 sm:p-12 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <h2 class="h-section text-white">{{ __('home.visit.title') }}</h2>
                    <p class="lede mt-4 text-white/65">
                        {{ __('home.visit.lede', [
                            'address' => setting('address_line'),
                            'city' => setting('address_city'),
                        ]) }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('contact') }}" class="btn-accent">
                            <x-icon name="map-pin" size="16" /> {{ __('home.visit.directions_cta') }}
                        </a>
                        <a href="tel:{{ setting('hotline') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                            <x-icon name="phone" size="16" /> {{ setting('hotline') }}
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="grid gap-3" data-reveal-stagger="80">
                        @foreach ([
                            ['ambulance', __('home.visit.ambulance'), setting('ambulance_number'), 'tel:' . setting('ambulance_number')],
                            ['calendar', __('home.visit.appointments'), setting('appointment_number'), 'tel:' . setting('appointment_number')],
                            ['globe', __('home.visit.international'), setting('international_desk'), 'tel:' . setting('international_desk')],
                        ] as [$icon, $label, $value, $href])
                            <a href="{{ $href }}"
                               class="reveal group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4
                                      transition duration-300 ease-out hover:translate-x-1 hover:border-teal-400/30 hover:bg-white/10">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300
                                             transition duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-500/25">
                                    <x-icon :name="$icon" size="19" />
                                </span>
                                <span>
                                    <span class="block text-xs text-white/50">{{ $label }}</span>
                                    <span class="block font-display text-base font-bold text-white">{{ $value }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
