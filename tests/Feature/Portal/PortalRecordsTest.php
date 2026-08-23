<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortalRecordsTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
        ]);

        $this->patient = Patient::create([
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'password' => 'correct-horse-1',
        ]);
    }

    private function appointment(array $overrides = []): Appointment
    {
        static $counter = 0;
        $counter++;

        return Appointment::create(array_merge([
            'reference' => 'RBR'.str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
            'doctor_id' => $this->doctor->id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => sprintf('%02d:00:00', 8 + ($counter % 10)),
            'status' => 'confirmed',
        ], $overrides));
    }

    private function document(array $overrides = []): PatientDocument
    {
        return PatientDocument::create(array_merge([
            'phone' => '01712345678',
            'title' => 'Lipid Profile report',
            'category' => 'report',
            'path' => 'patient-documents/1712345678/abc123.pdf',
            'original_name' => 'lipid-profile.pdf',
            'mime' => 'application/pdf',
            'size' => 51200,
        ], $overrides));
    }

    public static function phoneFormats(): array
    {
        return [
            'as typed' => ['01712345678'],
            'with country code' => ['8801712345678'],
            'with a plus' => ['+8801712345678'],
        ];
    }

    #[DataProvider('phoneFormats')]
    public function test_appointments_are_found_whichever_way_the_number_was_written(string $stored): void
    {
        // Appointments keep the number exactly as it was typed, and the
        // booking form accepts three spellings of the same one.
        $this->appointment(['phone' => $stored]);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.appointments'))
            ->assertOk()
            ->assertSee('Dr. Farhana Islam');
    }

    public function test_the_dashboard_shows_what_is_coming_up(): void
    {
        $this->appointment();
        $this->appointment(['appointment_date' => Carbon::yesterday()->toDateString()]);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee(__('portal.dashboard.upcoming_title'))
            ->assertSee('Dr. Farhana Islam');
    }

    public function test_another_patients_appointments_are_invisible(): void
    {
        $this->appointment(['patient_name' => 'Rahim Uddin']);
        $this->appointment(['phone' => '01998887776', 'patient_name' => 'Someone Else', 'reference' => 'RBR9999999']);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.appointments'))
            ->assertOk()
            ->assertDontSee('RBR9999999');
    }

    public function test_documents_are_listed_for_the_signed_in_number(): void
    {
        $this->document();
        $this->document(['phone' => '01998887776', 'title' => 'Somebody else’s biopsy']);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.documents'))
            ->assertOk()
            ->assertSee('Lipid Profile report')
            ->assertDontSee('Somebody else’s biopsy', escape: false);
    }

    public function test_a_document_is_downloadable_by_its_owner(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('patient-documents/1712345678/abc123.pdf', 'the report');

        $document = $this->document();

        $response = $this->actingAs($this->patient, 'patient')
            ->get(route('portal.documents.download', $document));

        $response->assertOk();
        $response->assertDownload('lipid-profile.pdf');
        $this->assertNotNull($document->fresh()->downloaded_at);
    }

    public function test_a_document_belonging_to_someone_else_is_not_downloadable(): void
    {
        // The whole point of the private disk: knowing an id is not permission.
        Storage::fake('local');
        Storage::disk('local')->put('patient-documents/1998887776/xyz.pdf', 'not yours');

        $theirs = $this->document([
            'phone' => '01998887776',
            'path' => 'patient-documents/1998887776/xyz.pdf',
            'title' => 'Someone else’s biopsy',
        ]);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.documents.download', $theirs))
            ->assertNotFound();

        $this->assertNull($theirs->fresh()->downloaded_at);
    }

    public function test_a_guest_cannot_download_anything(): void
    {
        $document = $this->document();

        $this->get(route('portal.documents.download', $document))
            ->assertRedirect(route('portal.login'));
    }

    public function test_documents_can_be_filtered_by_type(): void
    {
        $this->document();
        $this->document(['category' => 'bill', 'title' => 'Invoice INV-4471']);

        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.documents', ['category' => 'bill']))
            ->assertOk()
            ->assertSee('Invoice INV-4471')
            ->assertDontSee('Lipid Profile report');
    }

    public function test_a_document_filed_before_registration_is_waiting_afterwards(): void
    {
        // The lab has a report before the patient gets round to signing up.
        $this->document(['phone' => '01555444333', 'title' => 'Earlier CBC report']);

        $newcomer = Patient::create([
            'name' => 'Nasrin Akter',
            'phone' => '01555444333',
            'password' => 'correct-horse-1',
        ]);

        $this->actingAs($newcomer, 'patient')
            ->get(route('portal.documents'))
            ->assertOk()
            ->assertSee('Earlier CBC report');
    }

    public function test_a_patient_can_update_their_details(): void
    {
        $this->actingAs($this->patient, 'patient')
            ->put(route('portal.profile.update'), [
                'name' => 'Md. Rahim Uddin',
                'email' => 'rahim@example.test',
                'gender' => 'male',
            ])->assertSessionHasNoErrors();

        $this->assertSame('Md. Rahim Uddin', $this->patient->fresh()->name);
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        // A borrowed unlocked phone should not become a stolen account.
        $this->actingAs($this->patient, 'patient')
            ->put(route('portal.profile.update'), [
                'name' => 'Rahim Uddin',
                'password' => 'a-brand-new-one',
                'password_confirmation' => 'a-brand-new-one',
            ])->assertSessionHasErrors('current_password');
    }

    public function test_the_mobile_number_cannot_be_changed_from_the_portal(): void
    {
        // It is the key everything is matched on: changing it would silently
        // move somebody else's records under this account.
        $this->actingAs($this->patient, 'patient')
            ->put(route('portal.profile.update'), [
                'name' => 'Rahim Uddin',
                'phone' => '01998887776',
            ])->assertSessionHasNoErrors();

        $this->assertSame('1712345678', $this->patient->fresh()->phone);
    }

    public function test_documents_never_land_on_the_public_disk(): void
    {
        // A guessable URL to somebody's biopsy result is not a mistake that
        // can be walked back.
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs(\App\Models\User::factory()->create())
            ->post(route('admin.documents.store'), [
                'phone' => '01712345678',
                'title' => 'Lipid Profile report',
                'category' => 'report',
                'file' => UploadedFile::fake()->create('report.pdf', 40, 'application/pdf'),
            ])->assertSessionHasNoErrors();

        $document = PatientDocument::sole();

        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
        $this->assertStringStartsWith('patient-documents/1712345678/', $document->path);
        // The stored name is not the one the file arrived with.
        $this->assertStringNotContainsString('report.pdf', $document->path);
    }
}
