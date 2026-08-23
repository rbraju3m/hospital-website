<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentSlotService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** A doctor plus a date and time that the slot engine currently reports as open. */
    private function bookableSlot(): array
    {
        $slots = app(AppointmentSlotService::class);

        foreach (Doctor::active()->where('accepts_online_appointment', true)->get() as $doctor) {
            $dates = $slots->availableDates($doctor, 14);

            if ($dates->isNotEmpty()) {
                $date = CarbonImmutable::parse($dates->first()['date']);

                return [$doctor, $date, $slots->slotsFor($doctor, $date)->first()['time']];
            }
        }

        $this->fail('No bookable slot found in the seeded schedule.');
    }

    public function test_a_patient_can_book_an_open_slot(): void
    {
        [$doctor, $date, $time] = $this->bookableSlot();

        $response = $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
            'gender' => 'male',
            'age' => 42,
            'visit_type' => 'new',
        ]);

        $appointment = Appointment::firstOrFail();

        $response->assertRedirect(URL::signedRoute('appointment.confirmed', $appointment));

        $this->assertSame($doctor->id, $appointment->doctor_id);
        $this->assertSame($doctor->department_id, $appointment->department_id);
        $this->assertSame('pending', $appointment->status);
        $this->assertMatchesRegularExpression('/^RBR\d{6}[A-Z0-9]{4}$/', $appointment->reference);

        $this->get(URL::signedRoute('appointment.confirmed', $appointment))
            ->assertOk()
            ->assertSee($appointment->reference);
    }

    public function test_the_confirmation_page_is_not_reachable_by_guessing_a_reference(): void
    {
        // It carries a patient's name, phone, age and gender behind nothing but
        // a reference short enough to enumerate. The link in the confirmation
        // email is signed; a guessed one is not.
        [$doctor, $date, $time] = $this->bookableSlot();

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ]);

        $appointment = Appointment::firstOrFail();

        $this->get(route('appointment.confirmed', $appointment))
            ->assertForbidden()
            ->assertDontSee('Rafiqul Islam');

        // A corrupted signature is no better than none. (Route binding runs
        // before the signature check, so swapping in a reference that does not
        // exist would 404 rather than prove anything.)
        $signed = URL::signedRoute('appointment.confirmed', $appointment);
        $tampered = preg_replace('/signature=\w/', 'signature=0', $signed, 1);

        $this->get($tampered)->assertForbidden();
    }

    public function test_the_same_slot_cannot_be_booked_twice(): void
    {
        [$doctor, $date, $time] = $this->bookableSlot();

        $payload = [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ];

        $this->post(route('appointment.store'), $payload)->assertRedirect();

        $this->post(route('appointment.store'), [...$payload, 'patient_name' => 'Someone Else'])
            ->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_a_slot_outside_the_doctors_schedule_is_rejected(): void
    {
        [$doctor, $date] = $this->bookableSlot();

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '03:00', // no consultant holds a 3am chamber
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ])->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_beyond_the_window_is_rejected(): void
    {
        [$doctor, , $time] = $this->bookableSlot();

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(AppointmentSlotService::BOOKING_WINDOW_DAYS + 5)->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ])->assertSessionHasErrors('appointment_date');
    }

    public function test_a_past_date_is_rejected(): void
    {
        [$doctor, , $time] = $this->bookableSlot();

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ])->assertSessionHasErrors('appointment_date');
    }

    public function test_consultants_who_decline_online_booking_are_refused(): void
    {
        [$doctor, $date, $time] = $this->bookableSlot();
        $doctor->update(['accepts_online_appointment' => false]);

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ])->assertSessionHasErrors('doctor_id');
    }

    public function test_the_slots_endpoint_returns_open_times_only(): void
    {
        [$doctor, $date, $time] = $this->bookableSlot();

        $this->getJson(route('appointment.slots', ['doctor_id' => $doctor->id, 'date' => $date->toDateString()]))
            ->assertOk()
            ->assertJsonPath('date', $date->toDateString())
            ->assertJsonFragment(['time' => $time]);

        $this->post(route('appointment.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
        ]);

        // Once taken, that time must disappear from the published slot list.
        $this->getJson(route('appointment.slots', ['doctor_id' => $doctor->id, 'date' => $date->toDateString()]))
            ->assertOk()
            ->assertJsonMissing(['time' => $time]);
    }

    public function test_the_doctors_endpoint_filters_by_department(): void
    {
        $doctor = Doctor::with('department')
            ->where('accepts_online_appointment', true)
            ->firstOrFail();

        $this->getJson(route('appointment.doctors', ['department' => $doctor->department->slug]))
            ->assertOk()
            ->assertJsonFragment(['slug' => $doctor->slug]);
    }
}
