<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\Setting;
use App\Support\SiteFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    private function album(array $attributes = [], array $captions = ['A ward at night']): GalleryAlbum
    {
        $album = GalleryAlbum::create(array_merge([
            'title' => 'Cardiac theatres',
            'slug' => 'cardiac-theatres',
            'summary' => 'Two cath labs and the hybrid theatre.',
        ], $attributes));

        foreach ($captions as $i => $caption) {
            $album->photos()->create(['caption' => $caption, 'sort_order' => $i + 1]);
        }

        return $album;
    }

    private function switchOff(string $key): void
    {
        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey($key)],
            ['value' => '0', 'group' => SiteFeatures::GROUP],
        );
        Setting::flushCache();
    }

    public function test_the_index_lists_albums_and_links_to_them(): void
    {
        $this->album();

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSee('Cardiac theatres')
            ->assertSee(route('gallery.show', 'cardiac-theatres'));
    }

    public function test_a_hidden_album_is_neither_listed_nor_reachable(): void
    {
        $album = $this->album(['is_active' => false]);

        $this->get(route('gallery.index'))->assertOk()->assertDontSee('Cardiac theatres');
        $this->get(route('gallery.show', $album))->assertNotFound();
    }

    public function test_an_album_page_renders_its_photographs_in_order(): void
    {
        $album = $this->album(attributes: [], captions: ['Cath lab one', 'The control room']);

        $response = $this->get(route('gallery.show', $album))->assertOk();

        $response->assertSee('Cath lab one')->assertSee('The control room');
        $response->assertSeeInOrder(['Cath lab one', 'The control room']);
    }

    public function test_an_album_with_no_photographs_says_so_rather_than_rendering_an_empty_grid(): void
    {
        $album = $this->album(captions: []);

        $this->get(route('gallery.show', $album))
            ->assertOk()
            ->assertSee(__('gallery.album.empty'));
    }

    public function test_switching_the_area_off_closes_the_pages_and_removes_the_links(): void
    {
        $album = $this->album();

        $this->switchOff('area_gallery');

        // Hiding the link is not enough on its own: the page has to stop
        // answering, or a bookmark still reaches it.
        $this->get(route('gallery.index'))->assertNotFound();
        $this->get(route('gallery.show', $album))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee(route('gallery.index'));
    }

    public function test_the_home_band_shows_photographs_and_can_be_switched_off_on_its_own(): void
    {
        $this->album();

        $this->get(route('home'))->assertOk()->assertSee(__('home.gallery.title'));

        $this->switchOff('home_gallery');

        // The band goes; the gallery itself stays up.
        $this->get(route('home'))->assertOk()->assertDontSee(__('home.gallery.title'));
        $this->get(route('gallery.index'))->assertOk();
    }

    public function test_the_about_page_carries_a_strip(): void
    {
        $this->album();

        $this->get(route('about'))->assertOk()->assertSee(__('pages.about.gallery_title'));
    }

    public function test_an_album_reads_in_the_active_locale(): void
    {
        $album = $this->album();
        $album->setTranslations('bn', ['title' => 'কার্ডিয়াক থিয়েটার'])->save();
        $album->photos()->first()->setTranslations('bn', ['caption' => 'রাতে একটি ওয়ার্ড'])->save();

        $this->withSession(['locale' => 'bn'])
            ->get(route('gallery.show', $album))
            ->assertOk()
            ->assertSee('কার্ডিয়াক থিয়েটার')
            ->assertSee('রাতে একটি ওয়ার্ড');
    }

    public function test_photographs_with_nothing_to_show_are_dropped_rather_than_rendered_empty(): void
    {
        // No upload and no stand-in imagery: the tile would be an empty frame.
        $album = $this->album(captions: ['A ward at night']);
        $this->switchOff('behaviour_demo_images');

        $this->get(route('gallery.show', $album))
            ->assertOk()
            ->assertSee(__('gallery.album.empty'))
            ->assertDontSee('A ward at night');
    }
}
