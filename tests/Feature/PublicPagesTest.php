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
}
