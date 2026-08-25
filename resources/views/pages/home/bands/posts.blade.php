@if (feature('home_posts') && feature('area_posts'))
<section class="section">
    <div class="shell">
        <x-section-heading
            :eyebrow="__('home.posts.eyebrow')"
            :title="__('home.posts.title')"
            :lede="__('home.posts.lede')"
            :link="route('posts.index')"
            :link-label="__('home.posts.link')"
            class="reveal" />

        <div class="mt-12 grid gap-5 md:grid-cols-3" data-reveal-stagger="90">
            @foreach ($posts as $post)
                <x-post-card :post="$post" class="reveal" />
            @endforeach
        </div>
    </div>
</section>
@endif
