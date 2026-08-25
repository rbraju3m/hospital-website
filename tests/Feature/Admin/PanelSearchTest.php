<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Post;
use App\Models\User;
use App\Support\PanelSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The palette's second half: finding a record rather than a screen.
 */
class PanelSearchTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $department = Department::create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'icon' => 'heart-pulse',
            'translations' => ['bn' => ['name' => 'হৃদরোগ বিভাগ']],
        ]);

        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'speciality' => 'Interventional cardiology',
            'translations' => ['bn' => ['name' => 'ডা. ফারহানা ইসলাম']],
        ]);
    }

    private function booking(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'reference' => 'RBR7K2M9X',
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->doctor->department_id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '17:20',
        ], $overrides));
    }

    private function search(string $term): array
    {
        return $this->getJson(route('admin.search', ['q' => $term]))
            ->assertOk()
            ->json('results');
    }

    public function test_a_booking_is_found_by_its_reference(): void
    {
        $booking = $this->booking();

        $results = $this->search('RBR7K2M9X');

        $this->assertSame('Rahim Uddin', $results[0]['label']);
        $this->assertSame(route('admin.appointments.show', $booking), $results[0]['url']);
        // The reference and the date, because one patient books twice.
        $this->assertStringContainsString('RBR7K2M9X', $results[0]['meta']);
    }

    public function test_a_booking_is_found_by_patient_name_and_by_phone(): void
    {
        $this->booking();

        $this->assertNotEmpty($this->search('Rahim'));
        $this->assertNotEmpty($this->search('01712345678'));
    }

    public function test_a_doctor_is_found_in_either_script(): void
    {
        $this->assertSame(
            route('admin.doctors.edit', $this->doctor),
            collect($this->search('Farhana'))->firstWhere('group', __('admin.nav.doctors'))['url'],
        );

        // In the Bangla panel, the Bangla spelling finds them too — the same
        // rule as the public site: the base column OR the active locale's
        // translation, never a COALESCE of the two. English keeps working
        // there, which is the case that actually comes up, because the source
        // text lives in the ordinary columns whatever the panel is set to.
        $bangla = fn (string $term) => $this->withSession(['locale' => 'bn'])
            ->getJson(route('admin.search', ['q' => $term]))
            ->assertOk()
            ->json('results');

        $this->assertNotEmpty($bangla('ফারহানা'));
        $this->assertNotEmpty($bangla('Farhana'));
    }

    public function test_content_is_found_across_sections(): void
    {
        Post::create(['title' => 'Five signs of heart trouble', 'slug' => 'five-signs', 'body' => 'Body']);
        Patient::create([
            'name' => 'Shirin Akter',
            'phone' => '1812345678',
            'password' => Hash::make('secret-password'),
        ]);

        $groups = fn (string $term) => array_column($this->search($term), 'group');

        $this->assertContains(__('admin.nav.posts'), $groups('heart'));
        $this->assertContains(__('admin.nav.patients'), $groups('Shirin'));
        $this->assertContains(__('admin.nav.departments'), $groups('Cardio'));
    }

    public function test_a_short_term_searches_nothing(): void
    {
        $this->booking();

        // One character would match half the panel, and the palette already
        // has the menu to show while somebody is still typing.
        $this->assertSame([], $this->search('R'));
        $this->assertSame([], $this->getJson(route('admin.search'))->assertOk()->json('results'));
    }

    public function test_wildcards_typed_into_the_box_are_not_wildcards(): void
    {
        $this->booking();

        // '%' would otherwise match every booking rather than none.
        $this->assertSame([], $this->search('%%'));
        $this->assertSame([], $this->search('Rah%m'));
    }

    public function test_each_section_is_capped(): void
    {
        foreach (range(1, PanelSearch::PER_SOURCE + 3) as $index) {
            $this->booking([
                'reference' => 'RBRCAP'.$index,
                'appointment_time' => '09:'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            ]);
        }

        // A palette is a way to reach one record, not a report.
        $this->assertCount(PanelSearch::PER_SOURCE, $this->search('Rahim'));
    }

    public function test_a_guest_cannot_search_the_panel(): void
    {
        auth()->logout();

        $this->booking();

        $this->get(route('admin.search', ['q' => 'Rahim']))->assertRedirect(route('admin.login'));
    }

    public function test_the_palette_is_told_where_to_ask(): void
    {
        $html = $this->get(route('admin.dashboard'))->getContent();

        // @js() escapes the slashes on the way into the attribute.
        $this->assertStringContainsString(str_replace('/', '\\/', route('admin.search')), $html);
        $this->assertStringContainsString(__('admin.palette.searching'), $html);
    }
}
