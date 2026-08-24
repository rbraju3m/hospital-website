<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\SiteFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Site controls: the switches that decide what the public site shows.
 *
 * The point of these is that hiding a link and closing its route are the same
 * action. A test that only checked the navigation would pass while every
 * switched-off page still answered 200 to anyone holding the URL.
 */
class SiteFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function switchOff(string $feature): void
    {
        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey($feature)],
            ['value' => '0', 'group' => SiteFeatures::GROUP],
        );

        Setting::flushCache();
    }

    public function test_every_switch_defaults_to_its_registry_value(): void
    {
        Setting::where('group', SiteFeatures::GROUP)->delete();
        Setting::flushCache();

        foreach (SiteFeatures::defaults() as $key => $default) {
            $this->assertSame($default, SiteFeatures::enabled($key), $key);
        }
    }

    public function test_the_site_is_untouched_until_something_is_switched_off(): void
    {
        $this->get('/')->assertOk();
        $this->get('/doctors')->assertOk();
        $this->get('/health-hub')->assertOk();
    }

    public static function gatedAreas(): array
    {
        return [
            'departments' => ['area_departments', '/departments'],
            'doctors' => ['area_doctors', '/doctors'],
            'services' => ['area_services', '/services'],
            'packages' => ['area_packages', '/health-packages'],
            'diagnostics' => ['area_diagnostics', '/diagnostics'],
            'health hub' => ['area_posts', '/health-hub'],
            'about' => ['area_about', '/about'],
            'international' => ['area_international', '/international-patients'],
            'emergency' => ['area_emergency', '/emergency'],
            'contact' => ['area_contact', '/contact'],
            'appointment' => ['area_appointment', '/appointment'],
            'portal' => ['area_portal', '/portal/login'],
        ];
    }

    #[DataProvider('gatedAreas')]
    public function test_a_switched_off_area_stops_answering(string $feature, string $path): void
    {
        $this->get($path)->assertOk();

        $this->switchOff($feature);

        $this->get($path)->assertNotFound();
    }

    #[DataProvider('gatedAreas')]
    public function test_staff_still_reach_a_switched_off_area(string $feature, string $path): void
    {
        $this->switchOff($feature);

        // Signed in to the panel, so a page can be checked before it goes back
        // on air. Patients get the 404 either way.
        $this->actingAs(User::factory()->create())
            ->get($path)
            ->assertOk();
    }

    public function test_a_switched_off_area_disappears_from_the_navigation(): void
    {
        $this->get('/')->assertSee(route('posts.index'));

        $this->switchOff('area_posts');

        $this->get('/')->assertDontSee(route('posts.index'));
    }

    public function test_a_home_section_can_be_hidden_without_closing_its_area(): void
    {
        $this->switchOff('home_testimonials');

        $this->get('/')
            ->assertOk()
            ->assertDontSee(__('home.testimonials.title'));

        // The doctors area is untouched, so its section is still there.
        $this->get('/')->assertSee(__('home.doctors.title'));
    }

    public function test_online_booking_can_be_closed_while_the_page_stays_up(): void
    {
        $this->switchOff('behaviour_online_booking');

        $this->get('/appointment')
            ->assertOk()
            ->assertSee(__('appointment.closed.title'));

        // The form is closed, not merely hidden: a hand-rolled POST is refused.
        $this->post('/appointment', [])->assertNotFound();
    }

    public function test_maintenance_mode_answers_503_with_the_emergency_numbers(): void
    {
        $this->switchOff('behaviour_maintenance');
        $this->get('/')->assertOk();

        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey('behaviour_maintenance')],
            ['value' => '1', 'group' => SiteFeatures::GROUP],
        );
        Setting::flushCache();

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertSee(setting('ambulance_number'))
            ->assertSee(setting('hotline'));

        // Staff keep browsing, and the panel is never behind this gate.
        $this->actingAs(User::factory()->create())->get('/')->assertOk();
    }

    public function test_the_panel_and_the_portal_stay_up_in_maintenance_mode(): void
    {
        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey('behaviour_maintenance')],
            ['value' => '1', 'group' => SiteFeatures::GROUP],
        );
        Setting::flushCache();

        $this->get(route('admin.login'))->assertOk();
        $this->get(route('portal.login'))->assertOk();
    }
}
