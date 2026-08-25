{{-- Guarded on the area as well as the band: every tile links into /gallery,
     which answers 404 once the area is switched off. --}}
@if (feature('home_gallery') && feature('area_gallery') && $galleryPhotos->isNotEmpty())
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.gallery.eyebrow')"
            :title="__('home.gallery.title')"
            :lede="__('home.gallery.lede', ['name' => setting('site_name')])"
            :link="route('gallery.index')"
            :link-label="__('home.gallery.link')"
            class="reveal" />

        <x-photo-strip :photos="$galleryPhotos" class="mt-12" />
    </div>
</section>
@endif
