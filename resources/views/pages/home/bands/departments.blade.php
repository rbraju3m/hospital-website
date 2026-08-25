@if (feature('home_departments') && feature('area_departments'))
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.centres.eyebrow')"
            :title="__('home.centres.title')"
            :lede="__('home.centres.lede')"
            :link="route('departments.index')"
            :link-label="__('home.centres.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal-stagger="80">
            @foreach ($centres as $department)
                <x-department-card :department="$department" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif
