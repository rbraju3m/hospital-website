<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalisationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_default_locale_renders_english(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en"', escape: false)
            ->assertSee(__('nav.items.doctors'));
    }

    public function test_choosing_a_locale_persists_it_across_requests(): void
    {
        $this->from('/')->get(route('locale.switch', 'bn'))
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'bn');

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="bn"', escape: false)
            // A key that IS translated in lang/bn.
            ->assertSee(__('nav.items.doctors', locale: 'bn'));
    }

    public function test_an_untranslated_key_falls_back_to_english(): void
    {
        app()->setLocale('bn');

        // Deliberately absent from lang/bn/home.php.
        $this->assertSame(
            __('home.hero.stat_beds', locale: 'en'),
            __('home.hero.stat_beds')
        );
    }

    public function test_an_unknown_locale_is_rejected(): void
    {
        $this->get('/locale/xx')->assertNotFound();
        $this->assertNull(session('locale'));
    }

    public function test_a_session_locale_outside_the_allow_list_is_ignored(): void
    {
        // A tampered session value must not steer the translator.
        $this->withSession(['locale' => '../../etc'])
            ->get('/')
            ->assertOk()
            ->assertSee('lang="en"', escape: false);
    }

    public function test_the_browser_language_is_honoured_when_nothing_is_stored(): void
    {
        $this->get('/', ['Accept-Language' => 'bn,en;q=0.8'])
            ->assertOk()
            ->assertSee('lang="bn"', escape: false);
    }

    public function test_every_locale_exposes_the_same_lang_files(): void
    {
        $names = fn (string $locale) => collect(File::files(lang_path($locale)))
            ->map->getFilenameWithoutExtension()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            $names('en'),
            $names('bn'),
            'Each locale needs a file per domain so translators have somewhere to put the keys.'
        );
    }

    public function test_translated_keys_exist_in_the_english_source(): void
    {
        // Guards against a Bangla key that no longer matches any English key —
        // it would silently never render.
        foreach (File::files(lang_path('bn')) as $file) {
            $domain = $file->getFilenameWithoutExtension();
            $translated = Arr::dot(require $file->getPathname());
            $source = Arr::dot(require lang_path("en/{$domain}.php"));

            foreach (array_keys($translated) as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $source,
                    "lang/bn/{$domain}.php defines [{$key}], which does not exist in lang/en/{$domain}.php."
                );
            }
        }
    }

    public function test_pages_render_in_every_available_locale(): void
    {
        foreach (array_keys(config('app.available_locales')) as $locale) {
            $this->withSession(['locale' => $locale])
                ->get('/')
                ->assertOk();

            $this->withSession(['locale' => $locale])
                ->get(route('appointment.create'))
                ->assertOk();
        }
    }
}
