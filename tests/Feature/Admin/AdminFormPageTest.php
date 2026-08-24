<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\PatientDocument;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every editing screen in the panel, rendered. These pages carry the panel's
 * only two-column layout and a Blade slot per aside, so a typo in one of them
 * is a 500 on the screen staff use most — and nothing else here renders them.
 */
class AdminFormPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function records(): array
    {
        $department = Department::create([
            'name' => 'Cardiac Sciences', 'slug' => 'cardiac-sciences', 'icon' => 'heart-pulse',
        ]);

        $doctor = Doctor::create([
            'department_id' => $department->id, 'name' => 'Dr Rahman', 'slug' => 'dr-rahman',
            'gender' => 'male', 'consultation_fee' => 1200,
        ]);

        return [
            'departments' => $department,
            'doctors' => $doctor,
            'services' => Service::create(['name' => 'Emergency', 'slug' => 'emergency', 'icon' => 'ambulance', 'category' => 'emergency']),
            'packages' => HealthPackage::create(['name' => 'Executive', 'slug' => 'executive', 'category' => 'executive', 'price' => 5000]),
            'diagnostics' => DiagnosticTest::create(['name' => 'CBC', 'slug' => 'cbc', 'category' => 'pathology', 'price' => 400]),
            'posts' => Post::create(['title' => 'Five signs', 'slug' => 'five-signs', 'category' => 'health-tips', 'body' => 'Body.']),
            'testimonials' => Testimonial::create(['patient_name' => 'A Patient', 'quote' => 'Good care.', 'rating' => 5]),
            'documents' => PatientDocument::create([
                'phone' => '1712345678', 'title' => 'Blood report', 'category' => 'report',
                'path' => 'documents/x.pdf', 'original_name' => 'report.pdf', 'mime' => 'application/pdf', 'size' => 2048,
            ]),
        ];
    }

    public function test_every_create_page_renders(): void
    {
        // A doctor has to exist for the front desk's booking form to have one.
        $this->records();

        foreach (['departments', 'doctors', 'services', 'packages', 'diagnostics', 'posts',
                  'testimonials', 'documents', 'users', 'appointments'] as $area) {
            $this->get(route("admin.{$area}.create"))->assertOk();
        }
    }

    public function test_every_edit_page_renders(): void
    {
        foreach ($this->records() as $area => $record) {
            $this->get(route("admin.{$area}.edit", $record))->assertOk();
        }

        $this->get(route('admin.users.edit', auth()->user()))->assertOk();
    }

    public function test_the_aside_column_reaches_the_page(): void
    {
        // A mistyped slot name drops the whole column silently — the page still
        // renders, and the switches that publish the record are simply gone.
        $department = $this->records()['departments'];

        $this->get(route('admin.departments.edit', $department))
            ->assertOk()
            ->assertSee('xl:grid-cols-[minmax(0,1fr)_21rem]', escape: false)
            ->assertSee('name="sort_order"', escape: false)
            ->assertSee('name="is_centre_of_excellence"', escape: false)
            ->assertSee('name="image"', escape: false)
            ->assertSee('name="meta_title"', escape: false);
    }
}
