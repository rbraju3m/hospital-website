@if (feature('home_doctors') && feature('area_doctors'))
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.doctors.eyebrow')"
            :title="__('home.doctors.title')"
            :lede="__('home.doctors.lede')"
            :link="route('doctors.index')"
            :link-label="__('home.doctors.link')"
            class="reveal" />

        <form action="{{ route('doctors.index') }}" method="GET" class="reveal mt-10">
            <div class="card flex flex-col gap-3 p-3 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-navy-900/35" />
                    <label for="home-doctor-q" class="sr-only">{{ __('home.doctors.search_label') }}</label>
                    <input id="home-doctor-q" type="search" name="q"
                           placeholder="{{ __('home.doctors.search_placeholder') }}"
                           class="input border-0 pl-11 shadow-none focus:ring-0">
                </div>

                <div class="sm:w-64">
                    <label for="home-doctor-dept" class="sr-only">{{ __('home.booker.department') }}</label>
                    <select id="home-doctor-dept" name="department" class="input border-0 shadow-none focus:ring-0">
                        <option value="">{{ __('common.all_departments') }}</option>
                        @foreach ($departmentOptions as $dept)
                            <option value="{{ $dept->slug }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary sm:px-8">{{ __('home.doctors.search_button') }}</button>
            </div>
        </form>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger="70">
            @foreach ($doctors as $doctor)
                <x-doctor-card :doctor="$doctor" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif
