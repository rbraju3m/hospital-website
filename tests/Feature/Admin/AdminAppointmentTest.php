<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '17:20',
            'visit_type' => 'new',
            'status' => 'confirmed',
        ], $overrides);
    }

    public function test_the_front_desk_books_an_appointment(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $appointment = Appointment::sole();

        $this->assertSame('front-desk', $appointment->source);
        $this->assertSame('confirmed', $appointment->status);
        // The department is denormalised from the doctor at write time.
        $this->assertSame($this->doctor->department_id, $appointment->department_id);
        $this->assertStringStartsWith('RBR', $appointment->reference);
    }

    public function test_the_front_desk_may_book_outside_the_public_window(): void
    {
        // The 30-day window and 60-minute lead time exist to protect an
        // unattended web form; staff can see the consultant's actual day.
        $this->post(route('admin.appointments.store'), $this->payload([
            'appointment_date' => now()->addDays(120)->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Appointment::count());
    }

    public function test_the_same_slot_cannot_be_booked_twice_from_the_desk(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());

        $this->post(route('admin.appointments.store'), $this->payload(['patient_name' => 'Karim Mia']))
            ->assertSessionHasErrors('appointment_time');

        $this->assertSame(1, Appointment::count());
    }

    public function test_a_malformed_phone_number_is_rejected(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Appointment::count());
    }

    public function test_the_status_is_changed_from_the_detail_page(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload(['status' => 'pending']));
        $appointment = Appointment::sole();

        $this->patch(route('admin.appointments.status', $appointment), ['status' => 'completed'])
            ->assertSessionHas('status');

        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_an_unknown_status_is_refused(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());
        $appointment = Appointment::sole();

        $this->patch(route('admin.appointments.status', $appointment), ['status' => 'invented'])
            ->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_the_listing_filters_by_status_and_doctor(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload(['status' => 'pending']));
        $this->post(route('admin.appointments.store'), $this->payload([
            'appointment_time' => '18:00',
            'patient_name' => 'Karim Mia',
            'status' => 'cancelled',
        ]));

        $this->get(route('admin.appointments.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Rahim Uddin')
            ->assertDontSee('Karim Mia');
    }

    public function test_the_listing_searches_by_reference_and_phone(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());
        $reference = Appointment::sole()->reference;

        $this->get(route('admin.appointments.index', ['q' => $reference]))
            ->assertOk()
            ->assertSee($reference);

        $this->get(route('admin.appointments.index', ['q' => '01712345678']))
            ->assertOk()
            ->assertSee('Rahim Uddin');
    }

    public function test_the_export_returns_the_filtered_rows_as_csv(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());

        $response = $this->get(route('admin.appointments.export'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Reference', $csv);
        $this->assertStringContainsString('Rahim Uddin', $csv);
        // The export is a record, so it carries the base name in every locale.
        $this->assertStringContainsString('Dr. Farhana Islam', $csv);
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $message = ContactMessage::create([
            'name' => 'Shirin Akter',
            'phone' => '01812345678',
            'message' => 'Do you offer paediatric cardiology?',
        ]);

        // fresh(): the column default lands in the database, not on the
        // instance that just inserted it.
        $this->assertFalse($message->fresh()->is_read);

        $this->get(route('admin.messages.show', $message))->assertOk();

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_a_message_can_be_marked_unread_again(): void
    {
        $message = ContactMessage::create([
            'name' => 'Shirin Akter', 'phone' => '01812345678',
            'message' => 'Question', 'is_read' => true,
        ]);

        $this->patch(route('admin.messages.read', $message))->assertSessionHas('status');

        $this->assertFalse($message->fresh()->is_read);
    }
}
