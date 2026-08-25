@if (feature('home_services') && feature('area_services'))
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.services.eyebrow')"
            :title="__('home.services.title')"
            :lede="__('home.services.lede')"
            :link="route('services.index')"
            :link-label="__('home.services.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="80">
            @foreach ($services as $service)
                <a href="{{ route('services.show', $service) }}"
                   class="card-interactive reveal group flex flex-col p-7">
                    <div class="flex items-center justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-navy-50 text-navy-800
                                     transition duration-300 ease-out group-hover:scale-105 group-hover:bg-navy-900 group-hover:dark:bg-navy-100 group-hover:text-white">
                            <x-icon :name="$service->icon" size="24" />
                        </span>
                        @if ($service->is_247)
                            <span class="chip-accent">{{ __('services.badge_247') }}</span>
                        @endif
                    </div>

                    <h3 class="mt-5 font-display text-lg font-bold text-navy-900 group-hover:text-teal-700">
                        {{ $service->name }}
                    </h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-navy-900/60">{{ $service->summary }}</p>
                    <span class="card-arrow mt-auto pt-5 text-sm font-semibold text-teal-700">
                        {{ __('common.learn_more') }} →
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
