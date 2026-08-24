<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public static function staticRoutes(): array
    {
        return [
            'home' => ['/'],
            'departments' => ['/departments'],
            'doctors' => ['/doctors'],
            'services' => ['/services'],
            'packages' => ['/health-packages'],
            'health hub' => ['/health-hub'],
            'about' => ['/about'],
            'emergency' => ['/emergency'],
            'international' => ['/international-patients'],
            'contact' => ['/contact'],
            'appointment' => ['/appointment'],
        ];
    }

    #[DataProvider('staticRoutes')]
    public function test_static_pages_render(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_detail_pages_render(): void
    {
        $this->get(route('departments.show', Department::first()))->assertOk();
        $this->get(route('doctors.show', Doctor::first()))->assertOk();
        $this->get(route('services.show', Service::first()))->assertOk();
        $this->get(route('packages.show', HealthPackage::first()))->assertOk();
        $this->get(route('posts.show', Post::published()->first()))->assertOk();
    }

    public function test_inactive_records_are_not_reachable(): void
    {
        $doctor = Doctor::first();
        $doctor->update(['is_active' => false]);

        $this->get(route('doctors.show', $doctor))->assertNotFound();
    }

    public function test_unpublished_posts_are_not_reachable(): void
    {
        $post = Post::published()->first();
        $post->update(['published_at' => now()->addWeek()]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_doctor_search_filters_by_department_and_term(): void
    {
        $doctor = Doctor::with('department')->first();

        $this->get(route('doctors.index', ['department' => $doctor->department->slug]))
            ->assertOk()
            ->assertSee($doctor->name, escape: false);

        $this->get(route('doctors.index', ['q' => 'zzzz-no-such-consultant']))
            ->assertOk()
            ->assertSee('No consultants match that search');
    }

    public function test_contact_form_stores_a_message(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Rafiqul Islam',
            'phone' => '01712345678',
            'message' => 'I would like a corporate screening quote for 40 employees.',
        ])->assertRedirect()->assertSessionHas('status', 'contact-sent');

        $this->assertDatabaseHas('contact_messages', ['phone' => '01712345678']);
    }

    public function test_contact_form_rejects_a_malformed_phone_number(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Rafiqul Islam',
            'phone' => '12345',
            'message' => 'I would like a corporate screening quote.',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_editorial_prose_renders_its_markup_rather_than_printing_it(): void
    {
        // The panel offers a toolbar that writes `## heading`, `- bullet` and
        // **bold**, and tells staff those work. Four pages used to split on
        // newlines and print the markers verbatim.
        $department = \App\Models\Department::firstOrFail();

        $department->forceFill(['description' => "## Our approach\n\n- Angioplasty around the clock\n- A **hybrid** theatre"])->save();

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee('<h2', escape: false)
            ->assertSee('Our approach')
            ->assertSee('<strong', escape: false)
            ->assertDontSee('## Our approach')
            ->assertDontSee('- Angioplasty');
    }

    public function test_the_whole_markup_language_renders(): void
    {
        $department = \App\Models\Department::firstOrFail();

        $department->forceFill(['description' => implode("\n\n", [
            '## Heading',
            '### Subheading',
            '- First bullet',
            "1. First step\n2. Second step",
            '> A quotation',
            '---',
            'Text with **bold**, _italic_ and a [link](https://example.com).',
        ])])->save();

        $response = $this->get(route('departments.show', $department))->assertOk();

        foreach (['<h2', '<h3', '<ul', '<ol', '<blockquote', '<hr', '<strong', '<em', '<a href="https://example.com"'] as $tag) {
            $response->assertSee($tag, escape: false);
        }

        // The markers themselves must not survive into the page.
        $response->assertDontSee('## Heading')->assertDontSee('> A quotation');
    }

    public function test_a_link_cannot_smuggle_a_script_url(): void
    {
        // A scheme allowlist, not a blocklist: anything outside it keeps the
        // label and loses the link.
        $department = \App\Models\Department::firstOrFail();

        $department->forceFill(['description' => '[Click me](javascript:alert(1))'])->save();

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertDontSee('javascript:alert', escape: false)
            ->assertSee('Click me')
            // The whole marker goes, brackets included: a URL containing its own
            // parentheses used to end the match early and leave one behind.
            ->assertDontSee('Click me)');
    }

    public function test_snake_case_words_are_not_turned_into_italics(): void
    {
        $department = \App\Models\Department::firstOrFail();

        $department->forceFill(['description' => 'The column is called sort_order in the database.'])->save();

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee('sort_order')
            ->assertDontSee('<em>', escape: false);
    }

    public function test_prose_markup_is_escaped_before_it_is_re_introduced(): void
    {
        $department = \App\Models\Department::firstOrFail();

        $department->forceFill(['description' => '<script>alert(1)</script> **bold**'])->save();

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee('&lt;script&gt;', escape: false);
    }
}
