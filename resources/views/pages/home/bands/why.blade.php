@if (feature('home_why'))
<section class="section bg-navy-900 dark:bg-navy-100 text-white">
    <div class="shell grid gap-14 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <p class="eyebrow text-teal-300">
                <span class="h-px w-6 bg-teal-400"></span>
                {{ __('home.why.eyebrow', ['name' => setting('site_name')]) }}
            </p>
            <h2 class="h-section mt-3 text-white">{{ __('home.why.title') }}</h2>
            <p class="lede mt-5 text-white/65">{{ __('home.why.lede') }}</p>

            <div class="mt-9 flex flex-wrap gap-3">
                @if (feature('area_about'))
                    <a href="{{ route('about') }}" class="btn-accent">{{ __('home.why.about_cta') }}</a>
                @endif
                @if (feature('area_contact'))
                    <a href="{{ route('contact') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                        {{ __('home.why.visit_cta') }}
                    </a>
                @endif
            </div>

            @php $whyImage = demo_image('cover', 'why-choose-us'); @endphp
            @if ($whyImage)
                {{-- One photograph, low in the column, so the band of reasons is
                     read against the place rather than against flat navy. --}}
                <figure class="media-frame reveal reveal-clip mt-10 hidden aspect-[16/10] border border-white/10 lg:block">
                    <img src="{{ $whyImage }}" alt="" loading="lazy" data-fade>
                    <figcaption class="media-badge bottom-4 start-4">
                        <x-icon name="map-pin" size="13" class="text-teal-300" />
                        {{ setting('address_city') }}
                    </figcaption>
                </figure>
            @endif
        </div>

        <div class="lg:col-span-7">
            <div class="grid gap-4 sm:grid-cols-2" data-reveal-stagger="80">
                @foreach ([
                    ['ambulance', __('home.why.triage_title'), __('home.why.triage_body')],
                    ['activity', __('home.why.icu_title'), __('home.why.icu_body', ['count' => setting('stat_icu_beds')])],
                    ['shield-check', __('home.why.accredited_title'), __('home.why.accredited_body')],
                    ['users', __('home.why.consultants_title', ['count' => setting('stat_doctors')]), __('home.why.consultants_body', ['departments' => setting('stat_departments')])],
                    ['heart-pulse', __('home.why.cardiac_title'), __('home.why.cardiac_body')],
                    ['file-text', __('home.why.reports_title'), __('home.why.reports_body')],
                ] as [$icon, $title, $body])
                    <div class="reveal group rounded-2xl border border-white/10 bg-white/5 p-6 transition duration-300 ease-out
                                hover:-translate-y-1 hover:border-teal-400/30 hover:bg-white/[0.08]">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-500/15 text-teal-300
                                     transition duration-300 ease-out group-hover:scale-110 group-hover:bg-teal-500/25">
                            <x-icon :name="$icon" size="20" />
                        </span>
                        <h3 class="mt-4 font-display text-base font-bold text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/60">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
