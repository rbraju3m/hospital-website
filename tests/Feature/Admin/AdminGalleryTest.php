<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function album(): GalleryAlbum
    {
        return GalleryAlbum::create(['title' => 'Cardiac theatres', 'slug' => 'cardiac-theatres']);
    }

    public function test_a_guest_cannot_reach_the_gallery_screens(): void
    {
        auth()->logout();

        $this->get(route('admin.gallery.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.gallery.store'), [])->assertRedirect(route('admin.login'));
    }

    public function test_an_album_is_created_with_a_generated_slug_and_lands_on_its_own_page(): void
    {
        $this->post(route('admin.gallery.store'), [
            'title' => 'Cardiac theatres',
            'summary' => 'Two cath labs.',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $album = GalleryAlbum::firstWhere('slug', 'cardiac-theatres');

        $this->assertNotNull($album);
        $this->assertSame('Two cath labs.', $album->untranslated('summary'));
    }

    public function test_an_album_stores_its_translations(): void
    {
        $album = $this->album();

        $this->put(route('admin.gallery.update', $album), [
            'title' => 'Cardiac theatres',
            'slug' => 'cardiac-theatres',
            'is_active' => '1',
            'translations' => ['bn' => ['title' => 'কার্ডিয়াক থিয়েটার']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('কার্ডিয়াক থিয়েটার', $album->fresh()->translation('title', 'bn'));
    }

    public function test_several_photographs_upload_in_one_go_and_keep_their_order(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->post(route('admin.gallery.photos.store', $album), [
            'photos' => [
                UploadedFile::fake()->image('cath-lab.jpg'),
                UploadedFile::fake()->image('control-room.jpg'),
                UploadedFile::fake()->image('recovery.png'),
            ],
        ])->assertSessionHasNoErrors();

        $photos = $album->photos()->get();

        $this->assertCount(3, $photos);
        $this->assertSame([1, 2, 3], $photos->pluck('sort_order')->all());

        foreach ($photos as $photo) {
            $this->assertStringStartsWith('gallery/', $photo->untranslated('path'));
            Storage::disk('public')->assertExists($photo->untranslated('path'));
        }
    }

    public function test_a_second_batch_lands_after_the_first(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->post(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('one.jpg')],
        ]);
        $this->post(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('two.jpg')],
        ]);

        $this->assertSame([1, 2], $album->photos()->get()->pluck('sort_order')->all());
    }

    public function test_an_upload_must_actually_be_an_image(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->post(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->create('scan.pdf', 200, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, $album->photos()->count());
    }

    public function test_an_upload_answers_with_the_photograph_it_created(): void
    {
        // The screen adds the tile from this payload rather than reloading.
        Storage::fake('public');
        $album = $this->album();

        $response = $this->postJson(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('cath-lab.jpg')],
        ])->assertCreated();

        $photo = $album->photos()->firstOrFail();

        $response->assertJsonPath('photos.0.id', $photo->id)
            ->assertJsonPath('photos.0.uploaded', true)
            ->assertJsonPath('photos.0.is_cover', false);
    }

    public function test_a_caption_saves_on_its_own_in_both_locales(): void
    {
        $album = $this->album();
        $photo = $album->photos()->create(['sort_order' => 1]);

        $this->patchJson(route('admin.gallery.photos.update', [$album, $photo]), [
            'caption' => 'Cath lab one',
            'translations' => ['bn' => ['caption' => 'ক্যাথ ল্যাব এক']],
        ])->assertOk()->assertJsonPath('photo.captions.bn', 'ক্যাথ ল্যাব এক');

        $photo->refresh();

        $this->assertSame('Cath lab one', $photo->untranslated('caption'));
        $this->assertSame('ক্যাথ ল্যাব এক', $photo->translation('caption', 'bn'));
    }

    public function test_dragging_a_photograph_saves_the_new_order(): void
    {
        $album = $this->album();
        $first = $album->photos()->create(['sort_order' => 1]);
        $second = $album->photos()->create(['sort_order' => 2]);

        $this->postJson(route('admin.gallery.photos.order', $album), ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame([$second->id, $first->id], $album->photos()->get()->pluck('id')->all());
    }

    public function test_a_photograph_cannot_be_reached_through_another_album(): void
    {
        $album = $this->album();
        $other = GalleryAlbum::create(['title' => 'Imaging', 'slug' => 'imaging']);
        $photo = $album->photos()->create(['sort_order' => 1, 'caption' => 'Right album']);

        $this->patchJson(route('admin.gallery.photos.update', [$other, $photo]), ['caption' => 'Wrong album'])
            ->assertNotFound();
        $this->postJson(route('admin.gallery.photos.cover', [$other, $photo]))->assertNotFound();
        $this->deleteJson(route('admin.gallery.photos.destroy', [$other, $photo]))->assertNotFound();

        $this->assertSame('Right album', $photo->fresh()->untranslated('caption'));

        // Ordering is scoped rather than guarded: an id from elsewhere is
        // simply not in the album's set.
        $this->postJson(route('admin.gallery.photos.order', $other), ['ids' => [$photo->id]])
            ->assertOk()
            ->assertJson(['ordered' => 0]);
    }

    public function test_removing_a_photograph_takes_its_file_with_it(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->postJson(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('cath-lab.jpg')],
        ]);

        $photo = $album->photos()->firstOrFail();
        $path = $photo->untranslated('path');

        $this->deleteJson(route('admin.gallery.photos.destroy', [$album, $photo]))->assertOk();

        $this->assertDatabaseMissing('gallery_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_photograph_can_be_promoted_to_the_album_cover(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->postJson(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('cath-lab.jpg')],
        ]);

        $photo = $album->photos()->firstOrFail();

        $this->postJson(route('admin.gallery.photos.cover', [$album, $photo]))->assertOk();

        $this->assertSame($photo->untranslated('path'), $album->fresh()->untranslated('image'));
    }

    public function test_promoting_a_photograph_does_not_destroy_the_album_own_cover(): void
    {
        // A cover somebody uploaded on the album form is a file they chose. One
        // click on a star must not be able to delete it.
        Storage::fake('public');
        $album = $this->album();

        $this->put(route('admin.gallery.update', $album), [
            'title' => 'Cardiac theatres',
            'slug' => 'cardiac-theatres',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('chosen-cover.jpg'),
        ])->assertSessionHasNoErrors();

        $chosen = $album->fresh()->untranslated('image');

        $this->postJson(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('inside.jpg')],
        ]);

        $this->postJson(route('admin.gallery.photos.cover', [$album, $album->photos()->firstOrFail()]))->assertOk();

        Storage::disk('public')->assertExists($chosen);
    }

    public function test_a_photograph_with_no_file_cannot_become_the_cover(): void
    {
        // A stand-in has no path to copy; writing one would freeze today's
        // placeholder into the row.
        $album = $this->album();
        $photo = $album->photos()->create(['sort_order' => 1]);

        $this->postJson(route('admin.gallery.photos.cover', [$album, $photo]))->assertStatus(422);

        $this->assertNull($album->fresh()->untranslated('image'));
    }

    public function test_deleting_the_photograph_used_as_the_cover_clears_it(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->postJson(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('cover.jpg')],
        ]);

        $photo = $album->photos()->firstOrFail();

        $this->postJson(route('admin.gallery.photos.cover', [$album, $photo]));
        $this->deleteJson(route('admin.gallery.photos.destroy', [$album, $photo]));

        // Otherwise the album points at a file that is no longer there.
        $this->assertNull($album->fresh()->untranslated('image'));
    }

    public function test_deleting_an_album_deletes_its_photographs_and_their_files(): void
    {
        Storage::fake('public');
        $album = $this->album();

        $this->post(route('admin.gallery.photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ]);

        $paths = $album->photos()->get()->map->untranslated('path');

        $this->delete(route('admin.gallery.destroy', $album))
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
        $this->assertSame(0, GalleryPhoto::count());

        // The rows cascade; the files would not without the controller.
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_the_album_screens_render(): void
    {
        $album = $this->album();
        $album->photos()->create(['caption' => 'Cath lab one', 'sort_order' => 1]);

        $this->get(route('admin.gallery.index'))->assertOk()->assertSee('Cardiac theatres');
        $this->get(route('admin.gallery.create'))->assertOk();
        $this->get(route('admin.gallery.edit', $album))
            ->assertOk()
            ->assertSee('Cath lab one')
            ->assertSee(__('admin.gallery.drop_here'));
    }
}
