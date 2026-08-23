<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPatientDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->staff = User::factory()->create();
        $this->actingAs($this->staff);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '01712345678',
            'title' => 'Lipid Profile report',
            'category' => 'report',
            'issued_at' => now()->toDateString(),
            'file' => UploadedFile::fake()->create('report.pdf', 40, 'application/pdf'),
        ], $overrides);
    }

    public function test_staff_publish_a_document_to_a_mobile_number(): void
    {
        $this->post(route('admin.documents.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $document = PatientDocument::sole();

        $this->assertSame('1712345678', $document->phone);
        $this->assertSame($this->staff->id, $document->uploaded_by);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_a_document_can_be_filed_before_the_patient_registers(): void
    {
        // The report exists before the account does.
        $this->post(route('admin.documents.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame(0, Patient::count());
        $this->assertSame(1, PatientDocument::count());
    }

    public function test_an_executable_upload_is_refused(): void
    {
        $this->post(route('admin.documents.store'), $this->payload([
            'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('file');

        $this->assertSame(0, PatientDocument::count());
    }

    public function test_an_oversized_upload_is_refused(): void
    {
        $this->post(route('admin.documents.store'), $this->payload([
            'file' => UploadedFile::fake()->create('huge.pdf', 20000, 'application/pdf'),
        ]))->assertSessionHasErrors('file');
    }

    public function test_replacing_the_file_removes_the_old_one(): void
    {
        // An orphan on the private disk is still somebody's medical record
        // sitting on a server.
        $this->post(route('admin.documents.store'), $this->payload());
        $document = PatientDocument::sole();
        $original = $document->path;

        $this->put(route('admin.documents.update', $document), $this->payload([
            'file' => UploadedFile::fake()->create('corrected.pdf', 40, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        Storage::disk('local')->assertMissing($original);
        Storage::disk('local')->assertExists($document->fresh()->path);
    }

    public function test_metadata_can_be_corrected_without_re_uploading(): void
    {
        $this->post(route('admin.documents.store'), $this->payload());
        $document = PatientDocument::sole();
        $path = $document->path;

        $this->put(route('admin.documents.update', $document), [
            'phone' => '01712345678',
            'title' => 'Lipid Profile report (corrected)',
            'category' => 'report',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Lipid Profile report (corrected)', $document->fresh()->title);
        $this->assertSame($path, $document->fresh()->path);
    }

    public function test_deleting_a_document_removes_the_file_too(): void
    {
        $this->post(route('admin.documents.store'), $this->payload());
        $document = PatientDocument::sole();

        $this->delete(route('admin.documents.destroy', $document))
            ->assertRedirect(route('admin.documents.index'));

        Storage::disk('local')->assertMissing($document->path);
        $this->assertSame(0, PatientDocument::count());
    }

    public function test_the_listing_says_whether_the_patient_can_actually_see_it(): void
    {
        // The question staff ask most: has it reached them?
        $this->post(route('admin.documents.store'), $this->payload());

        $this->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee(__('admin.documents.not_registered'));

        Patient::create(['name' => 'Rahim Uddin', 'phone' => '01712345678', 'password' => 'correct-horse-1']);

        $this->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee(__('admin.documents.registered_as', ['name' => 'Rahim Uddin']));
    }

    public function test_portal_access_can_be_switched_off(): void
    {
        $patient = Patient::create([
            'name' => 'Rahim Uddin', 'phone' => '01712345678', 'password' => 'correct-horse-1',
        ]);

        $this->patch(route('admin.patients.toggle', $patient))->assertSessionHas('status');

        $this->assertFalse($patient->fresh()->is_active);
    }

    public function test_a_guest_cannot_download_a_patient_document(): void
    {
        $this->post(route('admin.documents.store'), $this->payload());
        $document = PatientDocument::sole();

        auth()->logout();

        $this->get(route('admin.documents.download', $document))
            ->assertRedirect(route('admin.login'));
    }
}
