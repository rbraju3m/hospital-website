@if (feature('home_testimonials'))
<section class="section bg-mist-50">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.testimonials.eyebrow')"
            :title="__('home.testimonials.title')"
            :lede="__('home.testimonials.lede', ['name' => setting('site_name')])"
            align="center"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3" data-reveal-stagger="80">
            @foreach ($testimonials->take(6) as $testimonial)
                <x-testimonial-card :testimonial="$testimonial" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif
