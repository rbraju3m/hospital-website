{{-- The compact hero.

     A band rather than a stage: one line, the search that most visitors came
     to use, and the numbers — no photograph, no booking form, a fifth of the
     height. The compact layout is for a site whose visitors already know who
     they are dealing with and are looking for a department and a phone number,
     so the page starts where they are going rather than introducing itself. --}}
<section class="relative overflow-hidden bg-navy-900 dark:bg-navy-100">
    <div aria-hidden="true" class="hero-grid opacity-[0.10]"></div>
    <div aria-hidden="true" class="orb -right-32 -top-40 h-[24rem] w-[24rem] bg-teal-500/20"></div>

    <div class="shell relative py-10 lg:py-14">
        <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-6">
            <div class="min-w-0">
                <p class="eyebrow anim-fade-up text-teal-300">
                    <span class="h-px w-6 bg-teal-400"></span>
                    {{ setting('accreditation') }}
                </p>

                <h1 class="anim-fade-up mt-3 font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl"
                    style="--anim-delay:70ms">
                    {{ __('home.hero.heading_line_1') }}
                    {{ __('home.hero.heading_line_2_before') }}
                    <span class="text-teal-300">{{ __('home.hero.heading_line_2_accent') }}</span>
                </h1>
            </div>

            <div class="anim-fade-up flex flex-wrap items-center gap-3" style="--anim-delay:140ms">
                @if (feature('area_appointment'))
                    <a href="{{ route('appointment.create') }}" class="btn-accent btn-nudge">
                        <x-icon name="calendar-check" size="17" />
                        {{ __('common.book_appointment') }}
                        <x-icon name="arrow-right" size="17" />
                    </a>
                @endif

                <a href="tel:{{ setting('hotline') }}"
                   class="btn border border-white/25 text-white transition hover:border-white/50 hover:bg-white/10">
                    <x-icon name="phone-call" size="17" />
                    {{ setting('hotline') }}
                </a>
            </div>
        </div>

        {{-- The numbers stay: they are the shortest version of the case the
             taller hero spends a paragraph making. --}}
        @if (feature('home_stats'))
            <dl class="anim-fade-up mt-8 grid grid-cols-2 gap-x-8 gap-y-5 border-t border-white/10 pt-6 sm:grid-cols-4"
                style="--anim-delay:210ms">
                @foreach ([
                    ['stat_doctors', __('home.hero.stat_consultants'), '+'],
                    ['stat_beds', __('home.hero.stat_beds'), ''],
                    ['stat_departments', __('home.hero.stat_departments'), ''],
                    ['stat_years', __('home.hero.stat_years'), ''],
                ] as [$key, $label, $suffix])
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-white/45">{{ $label }}</dt>
                        <dd class="mt-1 font-display text-2xl font-extrabold text-white" data-countup>
                            {{ setting($key) }}{{ $suffix }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</section>
