<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use App\Support\HomeLayouts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Three arrangements of one home page, switched from the panel.
 *
 * A layout decides the order the bands come in and what the top of the page
 * looks like. It never decides what a band *says* — every one of them is the
 * same partial — so the thing worth testing is that no arrangement can lose a
 * section, and that nothing anybody can select in the panel produces an empty
 * home page.
 */
class HomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function slide(array $overrides = []): Slide
    {
        return Slide::create(array_merge([
            'title' => 'Emergency care, around the clock',
            'subtitle' => 'Consultant-led, every hour of every day.',
            'cta_label' => 'Call the hotline',
            'cta_url' => 'tel:+8809610001234',
            'is_active' => true,
        ], $overrides));
    }

    private function useLayout(string $layout): void
    {
        Setting::updateOrCreate(['key' => HomeLayouts::SETTING], ['value' => $layout]);
        Setting::flushCache();
    }

    public static function layouts(): array
    {
        return ['classic' => ['classic'], 'slider' => ['slider'], 'compact' => ['compact']];
    }

    #[DataProvider('layouts')]
    public function test_every_layout_renders_the_home_page(string $layout): void
    {
        $this->slide();
        $this->useLayout($layout);

        // A band every layout carries, rather than the hero — the slider
        // layout deliberately replaces that.
        $this->get(route('home'))->assertOk()->assertSee(e(__('home.centres.title')), false);
    }

    public function test_the_layout_decides_which_template_renders(): void
    {
        $this->useLayout('compact');

        $this->assertSame('pages.home.compact', HomeLayouts::view());
        $this->get(route('home'))->assertOk()->assertViewIs('pages.home.compact');
    }

    public function test_an_unknown_layout_falls_back_to_the_classic_one(): void
    {
        // A setting naming a layout that has been removed — or a hand-edited
        // row — must not blank the one page on the site that always has to
        // render. The registry is the source of truth, not the database.
        $this->useLayout('brochure-2019');

        $this->assertSame(HomeLayouts::DEFAULT, HomeLayouts::current());
        $this->get(route('home'))->assertOk()->assertViewIs('pages.home.classic');
    }

    public function test_the_slider_shows_active_slides_in_order(): void
    {
        $this->slide(['title' => 'Second', 'sort_order' => 2]);
        $this->slide(['title' => 'First', 'sort_order' => 1]);
        $this->slide(['title' => 'Hidden', 'sort_order' => 3, 'is_active' => false]);
        $this->useLayout('slider');

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Hidden', $html);
        $this->assertLessThan(strpos($html, 'Second'), strpos($html, 'First'));
    }

    public function test_the_slider_falls_back_to_the_hero_with_no_slides(): void
    {
        // Somebody switching the layout before writing the slides is a Tuesday,
        // and an empty band at the top of the home page is worse than the hero
        // they had before.
        $this->useLayout('slider');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(e(__('home.hero.heading_line_1')), false)
            ->assertDontSee('hero-slider', false);
    }

    public function test_a_slide_carries_its_buttons_and_only_the_ones_with_a_link(): void
    {
        $this->slide(['cta_secondary_label' => 'Ring the ward', 'cta_secondary_url' => null]);
        $this->useLayout('slider');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('tel:+8809610001234', false)
            // A button with a label and nowhere to go is not a button.
            ->assertDontSee('Ring the ward', false);
    }

    public function test_a_slide_reads_in_the_visitors_language(): void
    {
        $this->slide()->setTranslations('bn', ['title' => 'সার্বক্ষণিক জরুরি সেবা'])->save();
        $this->useLayout('slider');

        $this->withSession(['locale' => 'bn'])->get(route('home'))
            ->assertOk()
            ->assertSee('সার্বক্ষণিক জরুরি সেবা', false);
    }

    public function test_the_slides_are_in_the_markup_before_any_javascript_runs(): void
    {
        $this->slide(['title' => 'First', 'sort_order' => 1]);
        $this->slide(['title' => 'Second', 'sort_order' => 2]);
        $this->useLayout('slider');

        $html = $this->get(route('home'))->getContent();

        // Content must never need JavaScript to exist. The CSS hides the
        // second panel until the bundle reports in; the words are served.
        $this->assertStringContainsString('Second', $html);
        $this->assertStringContainsString('data-slide-hidden', $html);
    }

    #[DataProvider('layouts')]
    public function test_no_layout_loses_a_band(string $layout): void
    {
        $this->useLayout($layout);

        $html = $this->get(route('home'))->getContent();

        // Every band the classic layout renders is in the others too, in a
        // different order. A layout is an arrangement, not a subset.
        foreach ([__('home.centres.title'), __('home.doctors.title'), __('home.services.title')] as $heading) {
            $this->assertStringContainsString(e($heading), $html);
        }
    }

    public function test_a_band_switched_off_stays_off_in_every_layout(): void
    {
        $this->useLayout('compact');
        \App\Support\SiteFeatures::store(['home_doctors' => false]);

        $this->get(route('home'))->assertOk()->assertDontSee(e(__('home.doctors.title')), false);
    }

    public function test_the_panel_chooses_the_layout(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.site.update'), ['home_layout' => 'slider'])
            ->assertSessionHasNoErrors();

        Setting::flushCache();

        $this->assertSame('slider', HomeLayouts::current());
    }

    public function test_the_panel_will_not_write_a_layout_that_does_not_exist(): void
    {
        $this->useLayout('slider');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.site.update'), ['home_layout' => 'brochure-2019']);

        Setting::flushCache();

        // The bad value is refused rather than written down and fallen back
        // from on every request afterwards.
        $this->assertSame('slider', HomeLayouts::current());
    }

    public function test_each_layout_looks_different_from_the_others(): void
    {
        $this->slide();

        $pages = [];

        foreach (['classic', 'slider', 'compact'] as $layout) {
            $this->useLayout($layout);
            $pages[$layout] = $this->get(route('home'))->assertOk()->getContent();
        }

        // Classic and slider differ at the top of the page.
        $this->assertStringContainsString('hero-slider', $pages['slider']);
        $this->assertStringNotContainsString('hero-slider', $pages['classic']);

        /* Compact has its own hero rather than the tall one — a reordering
           alone is too quiet to read as a different layout, which is what it
           was on the first attempt. */
        $this->assertStringNotContainsString('ken-burns', $pages['compact']);
        $this->assertStringContainsString('ken-burns', $pages['classic']);

        // And its order genuinely differs: packages before the case-making.
        $this->assertLessThan(
            strpos($pages['compact'], e(__('home.why.title'))),
            strpos($pages['compact'], e(__('home.packages.title'))),
        );
        $this->assertGreaterThan(
            strpos($pages['classic'], e(__('home.why.title'))),
            strpos($pages['classic'], e(__('home.packages.title'))),
        );
    }
}
