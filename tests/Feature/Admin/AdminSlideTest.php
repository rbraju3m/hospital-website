<?php

namespace Tests\Feature\Admin;

use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSlideTest extends TestCase
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
            'eyebrow' => 'Open around the clock',
            'title' => 'Emergency care that does not wait',
            'subtitle' => 'Consultant-led, every hour of every day.',
            'cta_label' => 'Call the hotline',
            'cta_url' => 'tel:+8809610001234',
            'is_active' => 1,
            'sort_order' => 1,
            'translations' => ['bn' => ['title' => 'সার্বক্ষণিক জরুরি সেবা']],
        ], $overrides);
    }

    public function test_a_slide_is_created_in_both_languages(): void
    {
        $this->post(route('admin.slides.store'), $this->payload())->assertSessionHasNoErrors();

        $slide = Slide::sole();

        $this->assertSame('Emergency care that does not wait', $slide->untranslated('title'));
        $this->assertSame('সার্বক্ষণিক জরুরি সেবা', $slide->translation('title', 'bn'));
        $this->assertTrue($slide->is_active);
    }

    public function test_a_button_link_must_be_somewhere_a_button_can_point(): void
    {
        // The same allowlist the markup editor applies. `javascript:` in a
        // field a staff member fills in is a way to run something in a
        // visitor's browser.
        $this->post(route('admin.slides.store'), $this->payload(['cta_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('cta_url');

        $this->post(route('admin.slides.store'), $this->payload(['cta_url' => '/appointment']))
            ->assertSessionHasNoErrors();
    }

    public function test_an_image_is_stored_and_replaced_rather_than_piling_up(): void
    {
        Storage::fake('public');

        $this->post(route('admin.slides.store'), $this->payload([
            'image' => UploadedFile::fake()->image('slide.jpg', 1600, 900),
        ]))->assertSessionHasNoErrors();

        $slide = Slide::sole();
        $first = $slide->untranslated('image');

        Storage::disk('public')->assertExists($first);

        $this->put(route('admin.slides.update', $slide), $this->payload([
            'image' => UploadedFile::fake()->image('replacement.jpg', 1600, 900),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($slide->fresh()->untranslated('image'));
    }

    public function test_deleting_a_slide_takes_its_file_with_it(): void
    {
        Storage::fake('public');

        $this->post(route('admin.slides.store'), $this->payload([
            'image' => UploadedFile::fake()->image('slide.jpg', 1600, 900),
        ]));

        $slide = Slide::sole();
        $path = $slide->untranslated('image');

        $this->delete(route('admin.slides.destroy', $slide))->assertRedirect(route('admin.slides.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, Slide::count());
    }

    public function test_a_slide_with_no_upload_still_has_a_picture(): void
    {
        $slide = Slide::create(['title' => 'Emergency care']);

        // Stand-in photography, like every other picture on the site — which
        // is what lets the seeded slides ship as a working slider.
        $this->assertNotNull($slide->url());
    }

    public function test_the_listing_says_when_slides_are_not_on_the_site(): void
    {
        Slide::create(['title' => 'Emergency care']);

        // The layout is `classic` by default, so nothing here reaches a visitor
        // and the screen has to say so.
        $this->get(route('admin.slides.index'))
            ->assertOk()
            ->assertSee(__('admin.slides.not_showing'));

        \App\Support\SiteFeatures::store([]);
        $this->put(route('admin.site.update'), ['home_layout' => 'slider']);

        $this->get(route('admin.slides.index'))
            ->assertOk()
            ->assertDontSee(__('admin.slides.not_showing'));
    }

    public function test_slides_reorder_and_toggle_from_the_listing(): void
    {
        $first = Slide::create(['title' => 'First', 'sort_order' => 1]);
        $second = Slide::create(['title' => 'Second', 'sort_order' => 2]);

        $this->postJson(route('admin.lists.order', 'slides'), ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertLessThan($first->fresh()->sort_order, $second->fresh()->sort_order);

        $this->patchJson(route('admin.lists.toggle', ['list' => 'slides', 'id' => $first->id]))->assertOk();

        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_an_editor_manages_slides_and_the_front_desk_does_not(): void
    {
        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.slides.index'))->assertOk();

        $this->actingAs(User::factory()->frontDesk()->create())
            ->get(route('admin.slides.index'))->assertForbidden();
    }
}
