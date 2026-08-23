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

        // lang/bn is complete today, so assert the mechanism itself rather than
        // relying on a specific gap: register a key in English only and confirm
        // it renders its English value under bn, not the raw key.
        app('translator')->addLines(['probe.only_in_english' => 'Fallback value'], 'en');

        $this->assertSame('Fallback value', __('probe.only_in_english'));
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

    public function test_the_locales_define_exactly_the_same_keys(): void
    {
        foreach (File::files(lang_path('en')) as $file) {
            $domain = $file->getFilenameWithoutExtension();
            $source = Arr::dot(require $file->getPathname());
            $translated = Arr::dot(require lang_path("bn/{$domain}.php"));

            $this->assertSame(
                [],
                array_diff(array_keys($source), array_keys($translated)),
                "lang/bn/{$domain}.php is missing keys that lang/en/{$domain}.php defines."
            );

            // A Bangla key with no English counterpart would silently never render.
            $this->assertSame(
                [],
                array_diff(array_keys($translated), array_keys($source)),
                "lang/bn/{$domain}.php defines keys that lang/en/{$domain}.php does not."
            );
        }
    }

    public function test_translations_preserve_every_placeholder(): void
    {
        // A dropped :count or :name renders as a literal gap in the sentence,
        // which no amount of page-level testing would catch.
        foreach (File::files(lang_path('en')) as $file) {
            $domain = $file->getFilenameWithoutExtension();
            $source = Arr::dot(require $file->getPathname());
            $translated = Arr::dot(require lang_path("bn/{$domain}.php"));

            foreach ($source as $key => $value) {
                $this->assertSame(
                    $this->placeholders($value),
                    $this->placeholders($translated[$key] ?? ''),
                    "Placeholders differ between locales for [{$domain}.{$key}]."
                );
            }
        }
    }

    /** @return list<string> */
    private function placeholders(string $line): array
    {
        preg_match_all('/:([a-z_]+)/', $line, $matches);
        $found = array_unique($matches[1]);
        sort($found);

        return array_values($found);
    }

    public function test_dates_follow_the_active_locale(): void
    {
        // Carbon carries its own locale; the middleware has to set it too, or
        // month and weekday names stay English on an otherwise Bangla page.
        $this->withSession(['locale' => 'bn'])
            ->get(route('doctors.index'))
            ->assertOk();

        $this->assertSame('bn', \Illuminate\Support\Carbon::getLocale());
        $this->assertSame(
            'সোমবার',
            \Illuminate\Support\Carbon::parse('2026-08-24')->translatedFormat('l')
        );
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
