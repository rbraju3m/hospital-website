<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\SiteFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteControlTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create();
    }

    public function test_a_guest_cannot_reach_site_controls(): void
    {
        $this->get(route('admin.site.edit'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.site.update'), [])->assertRedirect(route('admin.login'));
    }

    public function test_the_page_lists_every_switch(): void
    {
        $response = $this->actingAs($this->staff())->get(route('admin.site.edit'))->assertOk();

        foreach (SiteFeatures::keys() as $key) {
            $response->assertSee("features[{$key}]", escape: false);
        }
    }

    public function test_saving_writes_every_key_not_only_the_ticked_ones(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.site.update'), ['features' => ['area_doctors' => '1']])
            ->assertRedirect();

        // An unticked box posts nothing, so the controller has to write the
        // whole registry — otherwise switching something off would do nothing.
        $this->assertTrue(SiteFeatures::enabled('area_doctors'));
        $this->assertFalse(SiteFeatures::enabled('area_posts'));

        $this->assertDatabaseHas('settings', [
            'key' => SiteFeatures::settingKey('area_posts'),
            'value' => '0',
            'group' => SiteFeatures::GROUP,
        ]);
    }

    public function test_a_key_that_is_not_in_the_registry_is_ignored(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.site.update'), ['features' => ['not_a_feature' => '1']])
            ->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => SiteFeatures::settingKey('not_a_feature')]);
        $this->assertDatabaseMissing('settings', ['key' => 'not_a_feature']);
    }

    public function test_saving_takes_effect_on_the_public_site_immediately(): void
    {
        // Same request cycle wrote the value; the settings cache has to have
        // been busted or the site would keep serving the old state.
        $this->actingAs($this->staff())
            ->put(route('admin.site.update'), ['features' => []]);

        $this->assertFalse(SiteFeatures::enabled('area_doctors'));
    }

    public function test_the_switches_do_not_appear_on_the_settings_page(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.site.update'), ['features' => ['area_doctors' => '1']]);

        // Site settings edits text somebody types; a switch has no business
        // being rendered there as a free-text field.
        $this->actingAs($this->staff())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertDontSee('values['.SiteFeatures::settingKey('area_doctors').']', escape: false);
    }

    public function test_the_seeder_is_idempotent_and_keeps_a_switched_off_value(): void
    {
        Setting::updateOrCreate(
            ['key' => SiteFeatures::settingKey('area_posts')],
            ['value' => '0', 'group' => SiteFeatures::GROUP],
        );
        Setting::flushCache();

        $this->seed(\Database\Seeders\SiteFeatureSeeder::class);

        $this->assertFalse(SiteFeatures::enabled('area_posts'));
    }

    public function test_the_all_on_and_all_off_buttons_target_their_group(): void
    {
        $html = $this->actingAs($this->staff())->get(route('admin.site.edit'))->assertOk()->getContent();

        /* `$el` inside a click handler is the button, not the section around
           it, so these have to walk up to the group before looking for
           switches. Bound to `$el` they searched an element containing none
           and silently did nothing — which is exactly how a broken button
           looks from the outside. */
        $this->assertStringContainsString('setGroup($el.closest(\'[data-control-group]\'), true)', $html);
        $this->assertStringContainsString('setGroup($el.closest(\'[data-control-group]\'), false)', $html);
        $this->assertStringNotContainsString('setGroup($el,', $html);

        // And the thing they walk up to is really there to be found.
        $this->assertStringContainsString('data-control-group', $html);
    }
}
