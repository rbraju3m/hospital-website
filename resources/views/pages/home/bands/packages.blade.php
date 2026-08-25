@if (feature('home_packages') && feature('area_packages'))
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.packages.eyebrow')"
            :title="__('home.packages.title')"
            :lede="__('home.packages.lede')"
            :link="route('packages.index')"
            :link-label="__('home.packages.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 lg:grid-cols-3" data-reveal-stagger="90">
            @foreach ($packages as $package)
                <x-package-card :package="$package" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif
