<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Setting;
use App\Support\DemoImages;
use App\Support\SiteFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stand-in photography: what the site shows in an image slot nobody has
 * uploaded a picture for.
 */
class DemoImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function switchOffStandIns(): void
    {
        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey('behaviour_demo_images')],
            ['value' => '0', 'group' => SiteFeatures::GROUP],
        );

        Setting::flushCache();
    }

    public function test_every_file_a_set_can_return_actually_exists(): void
    {
        // The counts in DemoImages are declared rather than globbed, so a file
        // deleted from public/ would otherwise only show up as a broken image.
        foreach (['doctor', 'cover', 'hero'] as $set) {
            foreach (DemoImages::set($set) as $path) {
                $this->assertFileExists(public_path(ltrim($path, '/')), $path);
            }
        }
    }

    public function test_a_pick_is_stable_for_the_same_seed(): void
    {
        $this->assertSame(
            DemoImages::pick('cover', 7, 'department'),
            DemoImages::pick('cover', 7, 'department'),
        );
    }

    public function test_consecutive_rows_do_not_share_an_image(): void
    {
        $picks = array_map(fn (int $id) => DemoImages::pick('cover', $id, 'department'), range(1, 8));

        $this->assertCount(8, array_unique($picks));
    }

    public function test_two_content_types_with_the_same_id_do_not_share_an_image(): void
    {
        $this->assertNotSame(
            DemoImages::pick('cover', 3, 'department'),
            DemoImages::pick('cover', 3, 'post'),
        );
    }

    public function test_a_portrait_matches_the_gender_recorded_on_the_doctor(): void
    {
        $female = collect(range(1, 40))->map(fn (int $id) => DemoImages::portrait('female', $id))->unique();
        $male = collect(range(1, 40))->map(fn (int $id) => DemoImages::portrait('male', $id))->unique();

        // The two pools never overlap, so a consultant is never shown a
        // photograph of somebody of the other gender.
        $this->assertTrue($female->intersect($male)->isEmpty());
        $this->assertTrue($female->every(fn (string $path) => file_exists(public_path(ltrim($path, '/')))));
    }

    public function test_an_uploaded_photograph_always_wins(): void
    {
        $doctor = Doctor::first();
        $doctor->update(['photo' => 'doctors/real-photo.webp']);

        $this->assertSame(media_url('doctors/real-photo.webp'), doctor_photo($doctor->fresh()));
    }

    public function test_stand_ins_can_be_switched_off_entirely(): void
    {
        $doctor = Doctor::whereNull('photo')->first();

        $this->assertNotNull(doctor_photo($doctor));

        $this->switchOffStandIns();

        $this->assertNull(doctor_photo($doctor));
        $this->assertNull(demo_image('cover', 1, 'post'));

        // With no photograph and no stand-in, the listing falls back to the
        // initials tile rather than rendering a broken image.
        $this->get('/doctors')->assertOk()->assertSee($doctor->initials());
    }

    public function test_the_doctor_listing_shows_a_stand_in_when_there_is_no_photograph(): void
    {
        $doctor = Doctor::whereNull('photo')->firstOrFail();

        $this->get('/doctors')
            ->assertOk()
            ->assertSee(DemoImages::portrait($doctor->gender, $doctor->id));
    }

    public function test_an_uploaded_image_is_addressed_without_a_hostname(): void
    {
        // The public disk builds its URLs from APP_URL, which pins every upload
        // to one hostname. Reached by any other name — artisan serve on
        // 127.0.0.1, the LAN address, a staging alias — those images 404 while
        // the root-relative stand-ins carry on working, so only the real
        // photographs vanish and nothing looks broken until someone notices.
        config(['app.url' => 'http://hospital.local']);

        $this->assertSame('/storage/gallery/ward.jpg', media_url('gallery/ward.jpg'));
    }

    public function test_an_image_on_another_host_keeps_its_hostname(): void
    {
        // There the hostname is the point.
        config(['app.url' => 'http://hospital.local']);
        config(['filesystems.disks.public.url' => 'https://cdn.example.com/media']);

        $this->assertSame('https://cdn.example.com/media/gallery/ward.jpg', media_url('gallery/ward.jpg'));
    }

    public function test_a_column_holding_an_absolute_url_is_left_alone(): void
    {
        $this->assertSame('https://example.com/x.jpg', media_url('https://example.com/x.jpg'));
        $this->assertSame('/images/demo/cover/cover-01.jpg', media_url('/images/demo/cover/cover-01.jpg'));
    }
}
