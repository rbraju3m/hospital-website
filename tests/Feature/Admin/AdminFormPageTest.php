<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\GalleryAlbum;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\PatientDocument;
use App\Models\Post;
use App\Models\Service;
use App\Models\Slide;
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
            'gallery' => GalleryAlbum::create(['title' => 'Cardiac theatres', 'slug' => 'cardiac-theatres']),
            'slides' => Slide::create(['title' => 'Care that is close by', 'eyebrow' => 'RBR Hospital']),
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
                  'gallery', 'slides', 'testimonials', 'documents', 'users', 'appointments'] as $area) {
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

    /**
     * A component tag Blade cannot parse is left in the output verbatim, and a
     * browser renders an unknown element as nothing at all: a required field
     * is simply not on the form, on a page that still answers 200. A double
     * quote inside a double-quoted attribute did that to the document category
     * select, and a report cannot be published without one.
     */
    public function test_no_form_page_ships_an_uncompiled_component_tag(): void
    {
        $urls = [
            route('admin.users.create'),
            route('admin.appointments.create'),
            route('admin.users.edit', auth()->user()),
        ];

        foreach ($this->records() as $area => $record) {
            $urls[] = route("admin.{$area}.create");
            $urls[] = route("admin.{$area}.edit", $record);
        }

        foreach ($urls as $url) {
            $this->assertStringNotContainsString(
                '<x-', $this->get($url)->assertOk()->getContent(),
                $url.' shipped a component tag Blade never compiled.',
            );
        }
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
