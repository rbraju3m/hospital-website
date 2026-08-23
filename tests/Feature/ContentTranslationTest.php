<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTranslationTest extends TestCase
{
    use RefreshDatabase;

    /** Models whose editorial content is expected to be fully translated. */
    private const TRANSLATED_MODELS = [
        Department::class,
        Doctor::class,
        Service::class,
        HealthPackage::class,
        Testimonial::class,
        Post::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_attributes_resolve_to_the_active_locale(): void
    {
        $department = Department::where('slug', 'cardiac-sciences')->firstOrFail();

        app()->setLocale('en');
        $english = $department->name;

        app()->setLocale('bn');
        $bangla = $department->name;

        $this->assertNotSame($english, $bangla);
        $this->assertMatchesRegularExpression('/\p{Bengali}/u', $bangla);

        // The stored value is still reachable regardless of locale.
        $this->assertSame($english, $department->untranslated('name'));
    }

    public function test_json_attributes_translate_as_arrays(): void
    {
        app()->setLocale('bn');
        $department = Department::where('slug', 'cardiac-sciences')->firstOrFail();

        $this->assertIsArray($department->highlights);
        $this->assertNotEmpty($department->highlights);
        $this->assertMatchesRegularExpression('/\p{Bengali}/u', $department->highlights[0]);
    }

    public function test_slugs_are_never_translated(): void
    {
        app()->setLocale('bn');

        // Route keys must stay stable so URLs do not fork per locale.
        $this->assertSame('cardiac-sciences', Department::where('slug', 'cardiac-sciences')->firstOrFail()->slug);
        $this->assertSame('pharmacy', Service::where('slug', 'pharmacy')->firstOrFail()->slug);
    }

    public function test_every_content_record_is_fully_translated_into_bangla(): void
    {
        foreach (self::TRANSLATED_MODELS as $class) {
            foreach ($class::all() as $record) {
                $this->assertSame(
                    [],
                    $record->missingTranslations('bn'),
                    class_basename($class)." [{$record->getKey()}] is missing Bangla for: "
                        .implode(', ', $record->missingTranslations('bn'))
                );
            }
        }
    }

    public function test_label_settings_are_translated(): void
    {
        foreach (Setting::TRANSLATABLE_KEYS as $key) {
            $setting = Setting::where('key', $key)->first();

            $this->assertNotNull($setting, "Setting [{$key}] does not exist.");
            $this->assertNotBlank(
                $setting->translation('value', 'bn'),
                "Setting [{$key}] has no Bangla value."
            );
        }
    }

    public function test_settings_are_cached_per_locale(): void
    {
        app()->setLocale('en');
        $english = setting('site_name');

        app()->setLocale('bn');
        $bangla = setting('site_name');

        // A single shared cache entry would serve the first locale to both.
        $this->assertNotSame($english, $bangla);
        $this->assertMatchesRegularExpression('/\p{Bengali}/u', $bangla);
    }

    public function test_doctors_are_searchable_in_both_scripts_under_bangla(): void
    {
        app()->setLocale('bn');

        $doctor = Doctor::where('slug', 'prof-dr-ashraful-haque')->firstOrFail();

        // A Bangla visitor may type either script.
        $this->assertTrue(
            Doctor::active()->search('আশরাফুল')->get()->contains($doctor),
            'Bangla name is not searchable under the bn locale.'
        );
        $this->assertTrue(
            Doctor::active()->search('Ashraful')->get()->contains($doctor),
            'English name is not searchable under the bn locale.'
        );
    }

    public function test_partial_column_selects_do_not_silently_drop_translations(): void
    {
        app()->setLocale('bn');

        // Regression guard: the nav composer once selected a column list that
        // omitted `translations`, so every menu item fell back to English.
        $withTranslations = Department::query()->first();
        $this->assertMatchesRegularExpression('/\p{Bengali}/u', $withTranslations->name);
    }

    public function test_the_appointment_doctor_endpoint_returns_translated_names(): void
    {
        // toArray() reads raw attributes, so the controller has to map by hand.
        $response = $this->withSession(['locale' => 'bn'])
            ->getJson(route('appointment.doctors', ['department' => 'cardiac-sciences']))
            ->assertOk();

        $name = $response->json('doctors.0.name');

        $this->assertMatchesRegularExpression('/\p{Bengali}/u', $name);
    }

    public function test_pages_render_translated_content(): void
    {
        $department = Department::where('slug', 'cardiac-sciences')->firstOrFail();
        $post = Post::published()->firstOrFail();

        app()->setLocale('bn');
        $banglaName = $department->name;
        $banglaTitle = $post->title;

        $this->withSession(['locale' => 'bn'])
            ->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee($banglaName, escape: false);

        $this->withSession(['locale' => 'bn'])
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee($banglaTitle, escape: false);
    }

    public function test_english_pages_are_unaffected(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('departments.show', 'cardiac-sciences'))
            ->assertOk()
            ->assertSee('Cardiac Sciences');
    }

    private function assertNotBlank(mixed $value, string $message = ''): void
    {
        $this->assertFalse(blank($value), $message);
    }
}
