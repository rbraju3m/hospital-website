<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\DiagnosticTest;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);
    }

    private function test(array $overrides = []): DiagnosticTest
    {
        return DiagnosticTest::create(array_merge([
            'name' => 'Lipid Profile',
            'slug' => 'lipid-profile',
            'code' => 'LIPID',
            'category' => 'pathology',
            'summary' => 'The standard cardiac risk panel.',
            'preparation' => 'Fast for 12 hours.',
            'report_time' => 'Same day',
            'price' => 1400,
            'is_active' => true,
        ], $overrides));
    }

    public function test_the_catalogue_lists_tests_with_prices(): void
    {
        $this->test();

        $this->get(route('diagnostics.index'))
            ->assertOk()
            ->assertSee('Lipid Profile')
            // Prices are integer taka rendered with a separator.
            ->assertSee('৳1,400', escape: false);
    }

    public function test_a_hidden_test_is_not_listed_or_reachable(): void
    {
        $hidden = $this->test(['is_active' => false]);

        $this->get(route('diagnostics.index'))->assertOk()->assertDontSee('Lipid Profile');
        $this->get(route('diagnostics.show', $hidden))->assertNotFound();
    }

    public function test_a_discounted_price_is_what_is_charged(): void
    {
        $this->test(['discount_price' => 1200]);

        $this->get(route('diagnostics.index'))
            ->assertOk()
            ->assertSee('৳1,200', escape: false);
    }

    public function test_tests_are_searchable_by_name(): void
    {
        $this->test();
        $this->test(['name' => 'Chest X-ray', 'slug' => 'chest-x-ray', 'code' => 'CXR', 'category' => 'imaging']);

        $this->get(route('diagnostics.index', ['q' => 'lipid']))
            ->assertOk()
            ->assertSee('Lipid Profile')
            ->assertDontSee('Chest X-ray');
    }

    public function test_tests_are_searchable_by_code(): void
    {
        // Patients read the code off a prescription as often as the name.
        $this->test();

        $this->get(route('diagnostics.index', ['q' => 'LIPID']))
            ->assertOk()
            ->assertSee('Lipid Profile');
    }

    public function test_the_category_filter_narrows_the_list(): void
    {
        $this->test();
        $this->test(['name' => 'Chest X-ray', 'slug' => 'chest-x-ray', 'code' => 'CXR', 'category' => 'imaging']);

        $this->get(route('diagnostics.index', ['category' => 'imaging']))
            ->assertOk()
            ->assertSee('Chest X-ray')
            ->assertDontSee('Lipid Profile');
    }

    public function test_an_unknown_category_is_ignored_rather_than_emptying_the_list(): void
    {
        $this->test();

        $this->get(route('diagnostics.index', ['category' => 'astrology']))
            ->assertOk()
            ->assertSee('Lipid Profile');
    }

    public function test_the_detail_page_carries_the_preparation(): void
    {
        $test = $this->test();

        $this->get(route('diagnostics.show', $test))
            ->assertOk()
            ->assertSee('Lipid Profile')
            ->assertSee('Fast for 12 hours.')
            ->assertSee(__('diagnostics.show.how_title'));
    }

    public function test_a_bangla_visitor_sees_the_bangla_catalogue(): void
    {
        $this->test(['translations' => ['bn' => [
            'name' => 'লিপিড প্রোফাইল',
            'preparation' => '১২ ঘণ্টা খালি পেটে থাকুন।',
        ]]]);

        $this->withSession(['locale' => 'bn'])
            ->get(route('diagnostics.index'))
            ->assertOk()
            ->assertSee('লিপিড প্রোফাইল')
            ->assertDontSee('Lipid Profile');
    }

    public function test_requesting_a_test_reaches_the_inbox(): void
    {
        $test = $this->test();

        $this->post(route('diagnostics.request', $test), [
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'notes' => 'Prefer Friday morning.',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $message = ContactMessage::sole();

        $this->assertSame('Rahim Uddin', $message->name);
        // The desk has to be able to read what was asked for, and for how much.
        $this->assertStringContainsString('Lipid Profile', $message->subject);
        $this->assertStringContainsString('LIPID', $message->message);
        $this->assertStringContainsString('1,400', $message->message);
        $this->assertStringContainsString('Prefer Friday morning.', $message->message);
        $this->assertFalse($message->fresh()->is_read);
    }

    public function test_a_request_needs_a_valid_bangladeshi_mobile(): void
    {
        $test = $this->test();

        $this->post(route('diagnostics.request', $test), [
            'name' => 'Rahim Uddin',
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_hidden_test_cannot_be_requested(): void
    {
        $hidden = $this->test(['is_active' => false]);

        $this->post(route('diagnostics.request', $hidden), [
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
        ])->assertNotFound();

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_the_catalogue_is_linked_from_the_navigation(): void
    {
        // The homepage tile promised "Diagnostics" long before this page
        // existed; it should now go somewhere real.
        $this->get('/')
            ->assertOk()
            ->assertSee(route('diagnostics.index'), escape: false);
    }

    public function test_a_request_from_a_bangla_visitor_still_reaches_the_desk_in_english(): void
    {
        // The inbox is staff-facing, and the appointment desk alert made the
        // same choice. The visitor still gets their confirmation in Bangla.
        $test = $this->test();

        $this->withSession(['locale' => 'bn'])
            ->post(route('diagnostics.request', $test), [
                'name' => 'Rahim Uddin',
                'phone' => '01712345678',
            ])->assertSessionHasNoErrors();

        $message = ContactMessage::sole();

        $this->assertSame(
            __('diagnostics.request.subject', ['test' => 'Lipid Profile'], 'en'),
            $message->subject
        );
        $this->assertStringContainsString('Requested test', $message->message);
    }
}
