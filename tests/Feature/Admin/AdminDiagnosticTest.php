<?php

namespace Tests\Feature\Admin;

use App\Models\DiagnosticTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lipid Profile',
            'code' => 'LIPID',
            'category' => 'pathology',
            'summary' => 'The standard cardiac risk panel.',
            'preparation' => 'Fast for 12 hours.',
            'sample_type' => 'Blood (serum)',
            'report_time' => 'Same day',
            'price' => 1400,
            'is_active' => '1',
            'is_home_collection' => '1',
        ], $overrides);
    }

    public function test_a_test_is_created_with_a_generated_slug(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('diagnostic_tests', [
            'slug' => 'lipid-profile',
            'code' => 'LIPID',
            'price' => 1400,
            'is_home_collection' => 1,
        ]);
    }

    public function test_translations_are_stored_but_the_code_is_not_translatable(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload([
            'translations' => ['bn' => [
                'name' => 'লিপিড প্রোফাইল',
                'preparation' => '১২ ঘণ্টা খালি পেটে থাকুন।',
            ]],
        ]))->assertSessionHasNoErrors();

        $test = DiagnosticTest::firstWhere('slug', 'lipid-profile');

        $this->assertSame('লিপিড প্রোফাইল', $test->translation('name', 'bn'));
        $this->assertSame('১২ ঘণ্টা খালি পেটে থাকুন।', $test->translation('preparation', 'bn'));
        // An order code is an identifier, so there is nowhere to translate it.
        $this->assertNotContains('code', array_keys($test->translations['bn']));
    }

    public function test_a_duplicate_code_is_refused(): void
    {
        // Two tests sharing an order code would send patients to the wrong
        // counter queue.
        $this->post(route('admin.diagnostics.store'), $this->payload());

        $this->post(route('admin.diagnostics.store'), $this->payload([
            'name' => 'Something else',
        ]))->assertSessionHasErrors('code');

        $this->assertSame(1, DiagnosticTest::count());
    }

    public function test_a_discount_must_be_below_the_price(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload(['discount_price' => 1500]))
            ->assertSessionHasErrors('discount_price');

        $this->assertSame(0, DiagnosticTest::count());
    }

    public function test_a_test_is_updated(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload());
        $test = DiagnosticTest::sole();

        $this->put(route('admin.diagnostics.update', $test), $this->payload([
            'slug' => 'lipid-profile',
            'price' => 1600,
            'discount_price' => 1300,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1600, $test->fresh()->price);
        $this->assertSame(1300, $test->fresh()->effectivePrice());
    }

    public function test_a_test_is_deleted(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload());
        $test = DiagnosticTest::sole();

        $this->delete(route('admin.diagnostics.destroy', $test))
            ->assertRedirect(route('admin.diagnostics.index'));

        $this->assertModelMissing($test);
    }

    public function test_the_listing_filters_by_category_and_searches_by_code(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload());
        $this->post(route('admin.diagnostics.store'), $this->payload([
            'name' => 'Chest X-ray', 'code' => 'CXR', 'category' => 'imaging',
        ]));

        $this->get(route('admin.diagnostics.index', ['category' => 'imaging']))
            ->assertOk()->assertSee('Chest X-ray')->assertDontSee('Lipid Profile');

        $this->get(route('admin.diagnostics.index', ['q' => 'CXR']))
            ->assertOk()->assertSee('Chest X-ray')->assertDontSee('Lipid Profile');
    }

    public function test_the_untranslated_filter_finds_gaps(): void
    {
        $this->post(route('admin.diagnostics.store'), $this->payload([
            'name' => 'Fully translated', 'code' => 'DONE',
            'translations' => ['bn' => [
                'name' => 'সম্পূর্ণ', 'summary' => 'সারাংশ', 'preparation' => 'প্রস্তুতি',
                'sample_type' => 'রক্ত', 'report_time' => 'একই দিনে',
            ]],
        ]));
        $this->post(route('admin.diagnostics.store'), $this->payload(['name' => 'Needs Bangla', 'code' => 'TODO']));

        $this->get(route('admin.diagnostics.index', ['untranslated' => 'bn']))
            ->assertOk()
            ->assertSee('Needs Bangla')
            ->assertDontSee('Fully translated');
    }
}
