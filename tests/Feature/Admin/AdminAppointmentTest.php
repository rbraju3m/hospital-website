<?php

namespace Tests\Feature\Admin;

use App\Mail\AppointmentMoved;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
            'locale' => 'en',
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

    public function test_the_desk_moves_a_booking_and_the_patient_is_told(): void
    {
        Mail::fake();

        $this->post(route('admin.appointments.store'), $this->payload(['email' => 'rahim@example.test']));
        $appointment = Appointment::sole();

        $this->put(route('admin.appointments.update', $appointment), $this->editPayload($appointment, [
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '11:40',
        ]))->assertSessionHasNoErrors();

        $appointment->refresh();

        $this->assertSame('11:40:00', $appointment->appointment_time);
        // The patient is not in the room when the desk does this.
        Mail::assertQueued(AppointmentMoved::class, fn ($mail) => $mail->hasTo('rahim@example.test'));
    }

    public function test_correcting_a_detail_does_not_text_the_patient(): void
    {
        Mail::fake();

        $this->post(route('admin.appointments.store'), $this->payload());
        $appointment = Appointment::sole();

        $this->put(route('admin.appointments.update', $appointment), $this->editPayload($appointment, [
            'patient_name' => 'Rahim Uddin Ahmed',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Rahim Uddin Ahmed', $appointment->fresh()->patient_name);

        // A spelling is not something to text somebody about: every message
        // costs a segment and some of their attention.
        Mail::assertNotQueued(AppointmentMoved::class);
    }

    public function test_changing_the_consultant_moves_the_department_with_it(): void
    {
        $other = Department::create(['name' => 'Neurology', 'slug' => 'neurology', 'icon' => 'brain']);
        $neurologist = Doctor::create([
            'department_id' => $other->id,
            'name' => 'Dr. Kamrul Hasan',
            'slug' => 'dr-kamrul-hasan',
        ]);

        $this->post(route('admin.appointments.store'), $this->payload());
        $appointment = Appointment::sole();

        $this->put(route('admin.appointments.update', $appointment), $this->editPayload($appointment, [
            'doctor_id' => $neurologist->id,
        ]))->assertSessionHasNoErrors();

        // Denormalised at write time, the same as when the booking was made.
        $this->assertSame($other->id, $appointment->fresh()->department_id);
    }

    public function test_a_booking_cannot_be_moved_on_top_of_another(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload(['appointment_time' => '17:20']));
        $this->post(route('admin.appointments.store'), $this->payload([
            'appointment_time' => '18:00', 'phone' => '01812345678', 'patient_name' => 'Other Patient',
        ]));

        $mine = Appointment::where('appointment_time', '18:00:00')->sole();

        $this->put(route('admin.appointments.update', $mine), $this->editPayload($mine, [
            'appointment_time' => '17:20',
        ]))->assertSessionHasErrors('appointment_time');

        $this->assertSame('18:00:00', $mine->fresh()->appointment_time);
    }

    public function test_the_edit_form_cannot_change_the_status(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload(['status' => 'confirmed']));
        $appointment = Appointment::sole();

        /* The status buttons on the show screen are the way to change it, and
           they are what tells the patient. A second path here would move a
           booking to `cancelled` and never say so. */
        $this->put(route('admin.appointments.update', $appointment), $this->editPayload($appointment, [
            'status' => 'cancelled',
        ]))->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_the_edit_screen_renders_the_booking_it_is_editing(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());
        $appointment = Appointment::sole();

        $this->get(route('admin.appointments.edit', $appointment))
            ->assertOk()
            ->assertSee($appointment->reference)
            ->assertSee('Rahim Uddin')
            // Set when the booking was made, and not offered again here.
            ->assertDontSee(__('admin.appointments.status_help'));
    }

    public function test_an_editor_cannot_touch_a_booking(): void
    {
        $this->post(route('admin.appointments.store'), $this->payload());
        $appointment = Appointment::sole();

        $this->actingAs(User::factory()->editor()->create(), 'web')
            ->get(route('admin.appointments.edit', $appointment))
            ->assertForbidden();
    }

    /** The edit form posts every field; these tests vary one at a time. */
    private function editPayload(Appointment $appointment, array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $appointment->doctor_id,
            'patient_name' => $appointment->patient_name,
            'phone' => $appointment->phone,
            'email' => $appointment->email,
            'appointment_date' => $appointment->appointment_date->toDateString(),
            'appointment_time' => substr($appointment->appointment_time, 0, 5),
            'visit_type' => $appointment->visit_type,
            'locale' => $appointment->locale,
        ], $overrides);
    }
}
