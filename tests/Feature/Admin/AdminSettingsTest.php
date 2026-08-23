<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);
    }

    public function test_a_setting_value_is_saved(): void
    {
        $this->put(route('admin.settings.update'), [
            'values' => ['site_name' => 'RBR Specialised Hospital', 'hotline' => '16263'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('RBR Specialised Hospital', Setting::firstWhere('key', 'site_name')->untranslated('value'));
        $this->assertSame('16263', Setting::firstWhere('key', 'hotline')->untranslated('value'));
    }

    public function test_a_translatable_setting_stores_a_bangla_value(): void
    {
        $this->put(route('admin.settings.update'), [
            'values' => ['site_name' => 'RBR Hospital', 'hotline' => '10666'],
            'translations' => ['bn' => ['site_name' => 'আরবিআর হাসপাতাল']],
        ])->assertSessionHasNoErrors();

        $setting = Setting::firstWhere('key', 'site_name');

        $this->assertSame('আরবিআর হাসপাতাল', $setting->translation('value', 'bn'));
    }

    public function test_saving_busts_the_cache_for_every_locale(): void
    {
        // Warm both locale caches first — a single shared key would serve
        // whichever locale warmed it to everybody.
        app()->setLocale('en');
        $this->assertSame('RBR Hospital', setting('site_name'));
        app()->setLocale('bn');
        setting('site_name');

        $this->put(route('admin.settings.update'), [
            'values' => ['site_name' => 'Renamed', 'hotline' => '10666'],
            'translations' => ['bn' => ['site_name' => 'নতুন নাম']],
        ]);

        app()->setLocale('en');
        $this->assertSame('Renamed', setting('site_name'));

        app()->setLocale('bn');
        $this->assertSame('নতুন নাম', setting('site_name'));
    }

    public function test_an_unknown_key_is_ignored_rather_than_created(): void
    {
        // Every key is read by name from a template, so inventing one from the
        // form would create a value nothing renders.
        $this->put(route('admin.settings.update'), [
            'values' => ['site_name' => 'RBR Hospital', 'invented_key' => 'nonsense'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('settings', ['key' => 'invented_key']);
    }
}
