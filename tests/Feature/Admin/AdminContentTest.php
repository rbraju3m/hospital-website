<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function departmentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cardiac Sciences',
            'icon' => 'heart-pulse',
            'summary' => 'Heart care.',
            'is_active' => '1',
            'is_centre_of_excellence' => '0',
        ], $overrides);
    }

    public function test_a_department_is_created_with_a_generated_slug(): void
    {
        $this->post(route('admin.departments.store'), $this->departmentPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'name' => 'Cardiac Sciences',
            'slug' => 'cardiac-sciences',
        ]);
    }

    public function test_a_generated_slug_does_not_collide(): void
    {
        Department::create(['name' => 'Cardiac Sciences', 'slug' => 'cardiac-sciences', 'icon' => 'heart-pulse']);

        $this->post(route('admin.departments.store'), $this->departmentPayload());

        $this->assertDatabaseHas('departments', ['slug' => 'cardiac-sciences-2']);
    }

    public function test_translated_fields_are_stored_in_the_translations_column(): void
    {
        $this->post(route('admin.departments.store'), $this->departmentPayload([
            'translations' => ['bn' => ['name' => 'কার্ডিয়াক সায়েন্সেস', 'summary' => 'হৃদরোগ চিকিৎসা।']],
        ]))->assertSessionHasNoErrors();

        $department = Department::firstWhere('slug', 'cardiac-sciences');

        // The base column keeps the fallback locale…
        $this->assertSame('Cardiac Sciences', $department->untranslated('name'));
        // …and the accessor swaps it out under a Bangla request.
        $this->assertSame('কার্ডিয়াক সায়েন্সেস', $department->translation('name', 'bn'));
    }

    public function test_clearing_a_translation_restores_the_fallback(): void
    {
        $department = Department::create([
            'name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse',
            'translations' => ['bn' => ['name' => 'কার্ডিওলজি']],
        ]);

        // An empty box has to *remove* the translation, not store "" — a stored
        // empty string and a missing key both fall back, but only the removal
        // leaves the column honest about what has been translated.
        $this->put(route('admin.departments.update', $department), $this->departmentPayload([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'translations' => ['bn' => ['name' => '']],
        ]))->assertSessionHasNoErrors();

        $this->assertNull($department->fresh()->translation('name', 'bn'));
    }

    public function test_list_columns_are_split_from_one_item_per_line(): void
    {
        $this->post(route('admin.departments.store'), $this->departmentPayload([
            'highlights' => "24/7 cath lab\nDedicated cardiac ICU\n\n  Rehab programme  ",
            'translations' => ['bn' => ['treatments' => "অ্যাঞ্জিওগ্রাম\nবাইপাস"]],
        ]))->assertSessionHasNoErrors();

        $department = Department::firstWhere('slug', 'cardiac-sciences');

        $this->assertSame(
            ['24/7 cath lab', 'Dedicated cardiac ICU', 'Rehab programme'],
            $department->untranslated('highlights')
        );
        $this->assertSame(['অ্যাঞ্জিওগ্রাম', 'বাইপাস'], $department->translation('treatments', 'bn'));
    }

    public function test_an_uploaded_image_is_stored_and_replaceable(): void
    {
        Storage::fake('public');

        $this->post(route('admin.departments.store'), $this->departmentPayload([
            'image' => UploadedFile::fake()->image('cardiology-ward.jpg'),
        ]))->assertSessionHasNoErrors();

        $department = Department::firstWhere('slug', 'cardiac-sciences');
        $original = $department->untranslated('image');

        $this->assertStringStartsWith('departments/', $original);
        Storage::disk('public')->assertExists($original);

        $this->put(route('admin.departments.update', $department), $this->departmentPayload([
            'slug' => 'cardiac-sciences',
            'image' => UploadedFile::fake()->image('new-ward.png'),
        ]));

        // The superseded file must not linger on disk.
        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertExists($department->fresh()->untranslated('image'));
    }

    public function test_an_image_can_be_removed(): void
    {
        Storage::fake('public');

        $this->post(route('admin.departments.store'), $this->departmentPayload([
            'image' => UploadedFile::fake()->image('ward.jpg'),
        ]));

        $department = Department::firstWhere('slug', 'cardiac-sciences');
        $path = $department->untranslated('image');

        $this->put(route('admin.departments.update', $department), $this->departmentPayload([
            'slug' => 'cardiac-sciences',
            'image_remove' => '1',
        ]));

        $this->assertNull($department->fresh()->untranslated('image'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_department_with_doctors_is_not_deleted(): void
    {
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        Doctor::create(['department_id' => $department->id, 'name' => 'Dr. A', 'slug' => 'dr-a']);

        $this->delete(route('admin.departments.destroy', $department))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_an_empty_department_is_deleted(): void
    {
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);

        $this->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_the_untranslated_filter_lists_only_incomplete_rows(): void
    {
        Department::create([
            'name' => 'Fully translated', 'slug' => 'done', 'icon' => 'activity',
            'translations' => ['bn' => ['name' => 'সম্পূর্ণ']],
        ]);
        Department::create(['name' => 'Needs Bangla', 'slug' => 'todo', 'icon' => 'activity']);

        $this->get(route('admin.departments.index', ['untranslated' => 'bn']))
            ->assertOk()
            ->assertSee('Needs Bangla')
            ->assertDontSee('Fully translated');
    }

    public function test_a_package_discount_must_be_below_the_price(): void
    {
        $this->post(route('admin.packages.store'), [
            'name' => 'Executive Screening',
            'category' => 'executive',
            'price' => 5000,
            'discount_price' => 6000,
        ])->assertSessionHasErrors('discount_price');

        $this->assertSame(0, HealthPackage::count());
    }

    public function test_a_post_slug_comes_from_its_title(): void
    {
        $this->post(route('admin.posts.store'), [
            'title' => 'Five signs of heat stroke',
            'category' => 'health-tips',
            'read_minutes' => 3,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', ['slug' => 'five-signs-of-heat-stroke']);
        $this->assertSame(1, Post::count());
    }

    public function test_editing_content_never_changes_its_slug_by_accident(): void
    {
        // URLs are the one thing that must not fork when a title is corrected.
        $post = Post::create(['title' => 'Old title', 'slug' => 'launch-announcement', 'category' => 'news', 'read_minutes' => 2]);

        $this->put(route('admin.posts.update', $post), [
            'title' => 'A much better title',
            'slug' => 'launch-announcement',
            'category' => 'news',
            'read_minutes' => 2,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('launch-announcement', $post->fresh()->slug);
        $this->assertSame('A much better title', $post->fresh()->untranslated('title'));
    }

    public function test_an_uploaded_photo_reaches_the_public_page(): void
    {
        // The whole point of the upload: a stored path resolves through the
        // storage:link symlink, which asset() would not have reached.
        Storage::fake('public');

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'is_active' => true,
        ]);

        $this->put(route('admin.doctors.update', $doctor), [
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'gender' => 'female',
            'consultation_fee' => 1500,
            'is_active' => '1',
            'photo' => UploadedFile::fake()->image('portrait.jpg'),
        ])->assertSessionHasNoErrors();

        $path = $doctor->fresh()->untranslated('photo');

        $this->get(route('doctors.show', $doctor))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path), escape: false);
    }

    public function test_a_page_without_an_uploaded_image_renders_no_cover(): void
    {
        // The conditional exists so the public design is untouched until
        // somebody actually uploads something.
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertDontSee('/storage/departments/', escape: false);
    }

    public function test_content_saved_without_a_summary_still_renders(): void
    {
        // Regression: @section('meta_description', $nullSummary) made Blade
        // treat the section as "capture until @endsection" and swallow the
        // whole page body. Nothing but the panel can create such a row.
        $this->post(route('admin.departments.store'), [
            'name' => 'Sleep Medicine',
            'icon' => 'bed',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $department = Department::firstWhere('slug', 'sleep-medicine');

        $this->assertNull($department->untranslated('summary'));

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee('Sleep Medicine')
            // Proof the body survived: this only appears far below the section.
            ->assertSee(__('departments.show.contact_title'));
    }
}
