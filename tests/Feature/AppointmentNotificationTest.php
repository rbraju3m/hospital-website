<?php

namespace Tests\Feature;

use App\Mail\AppointmentBooked;
use App\Mail\AppointmentStatusChanged;
use App\Mail\NewAppointmentAlert;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Setting::create(['key' => 'appointment_email', 'value' => 'desk@rbrhospital.test', 'group' => 'contact']);
        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'consultation_fee' => 2000,
            'is_active' => true,
            'accepts_online_appointment' => true,
        ]);

        // A Monday inside the booking window, with a chamber to match.
        $monday = Carbon::today()->next(Carbon::MONDAY);
        $this->date = $monday->toDateString();

        $this->doctor->schedules()->create([
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '20:00',
            'slot_minutes' => 20,
            'capacity_per_slot' => 1,
        ]);
    }

    private function publicBooking(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $this->date,
            'appointment_time' => '17:20',
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'email' => 'rahim@example.test',
            'visit_type' => 'new',
        ], $overrides);
    }

    private function withSessionLocale(string $locale): array
    {
        $this->withSession(['locale' => $locale]);

        return $this->publicBooking();
    }

    public function test_the_patient_is_emailed_when_they_book_online(): void
    {
        $this->post(route('appointment.store'), $this->publicBooking())
            ->assertSessionHasNoErrors();

        Mail::assertQueued(AppointmentBooked::class, fn ($mail) => $mail->hasTo('rahim@example.test'));
    }

    public function test_the_desk_is_alerted_to_a_website_booking(): void
    {
        $this->post(route('appointment.store'), $this->publicBooking());

        Mail::assertQueued(NewAppointmentAlert::class, fn ($mail) => $mail->hasTo('desk@rbrhospital.test'));
    }

    public function test_mail_is_queued_rather_than_sent_in_the_request(): void
    {
        // A slow mail server must never hold up the booking page.
        $this->post(route('appointment.store'), $this->publicBooking());

        Mail::assertNothingSent();
        Mail::assertQueuedCount(2);
    }

    public function test_a_patient_without_an_email_gets_none_but_the_desk_still_does(): void
    {
        // Email is optional on the booking form, and often blank.
        $this->post(route('appointment.store'), $this->publicBooking(['email' => null]))
            ->assertSessionHasNoErrors();

        Mail::assertNotQueued(AppointmentBooked::class);
        Mail::assertQueued(NewAppointmentAlert::class);
    }

    public function test_no_alert_goes_out_when_the_desk_address_is_blank(): void
    {
        Setting::where('key', 'appointment_email')->update(['value' => null]);
        Setting::flushCache();

        $this->post(route('appointment.store'), $this->publicBooking());

        Mail::assertNotQueued(NewAppointmentAlert::class);
        Mail::assertQueued(AppointmentBooked::class);
    }

    public function test_a_front_desk_booking_emails_the_patient_but_not_the_desk(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.appointments.store'), [
                'doctor_id' => $this->doctor->id,
                'patient_name' => 'Karim Mia',
                'phone' => '01812345678',
                'email' => 'karim@example.test',
                'appointment_date' => $this->date,
                'appointment_time' => '18:00',
                'visit_type' => 'new',
                'status' => 'confirmed',
                'locale' => 'bn',
            ])->assertSessionHasNoErrors();

        Mail::assertQueued(AppointmentBooked::class, fn ($mail) => $mail->hasTo('karim@example.test'));
        Mail::assertNotQueued(NewAppointmentAlert::class);
    }

    public function test_the_booking_locale_is_remembered_and_used(): void
    {
        $this->withSession(['locale' => 'bn'])
            ->post(route('appointment.store'), $this->publicBooking());

        $this->assertSame('bn', Appointment::sole()->locale);

        Mail::assertQueued(AppointmentBooked::class, fn ($mail) => $mail->locale === 'bn');
    }

    public function test_confirming_emails_the_patient_in_the_locale_they_booked_in(): void
    {
        $this->post(route('appointment.store'), $this->withSessionLocale('bn'));
        $appointment = Appointment::sole();

        // A staff member working in English confirms it…
        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', $appointment), ['status' => 'confirmed']);

        // …and the patient still hears about it in Bangla.
        Mail::assertQueued(
            AppointmentStatusChanged::class,
            fn ($mail) => $mail->hasTo('rahim@example.test') && $mail->locale === 'bn'
        );
    }

    public function test_cancelling_emails_the_patient(): void
    {
        $this->post(route('appointment.store'), $this->publicBooking());
        $appointment = Appointment::sole();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', $appointment), ['status' => 'cancelled']);

        Mail::assertQueued(AppointmentStatusChanged::class);
    }

    public function test_internal_status_moves_do_not_email_the_patient(): void
    {
        // "Completed" is bookkeeping after a visit that already happened, and
        // "pending" is where a booking starts — neither is news.
        $this->post(route('appointment.store'), $this->publicBooking());
        $appointment = Appointment::sole();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.appointments.status', $appointment), ['status' => 'completed']);

        Mail::assertNotQueued(AppointmentStatusChanged::class);
    }

    public function test_re_applying_the_same_status_sends_nothing(): void
    {
        $this->post(route('appointment.store'), $this->publicBooking());
        $appointment = Appointment::sole();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.appointments.status', $appointment), ['status' => 'confirmed']);
        $this->actingAs($admin)->patch(route('admin.appointments.status', $appointment), ['status' => 'confirmed']);

        // Two clicks, one email.
        Mail::assertQueuedCount(3); // booked + alert + one status change
    }

    public function test_a_status_change_on_a_patient_with_no_email_is_silent(): void
    {
        $this->post(route('appointment.store'), $this->publicBooking(['email' => null]));
        $appointment = Appointment::sole();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertSessionHas('status');

        Mail::assertNotQueued(AppointmentStatusChanged::class);
    }

    public function test_a_bangla_email_prints_bangla_month_names(): void
    {
        // Carbon keeps its own locale and Mailable::withLocale only moves the
        // translator, so a Bangla email would otherwise carry English month
        // and weekday names in the middle of a Bangla sentence.
        $this->withSession(['locale' => 'bn'])->post(route('appointment.store'), $this->publicBooking());

        $appointment = Appointment::sole();

        // Simulate the staff member's session being English when this renders.
        Carbon::setLocale('en');

        $rendered = (new AppointmentBooked($appointment))->locale('bn')->render();

        $date = Carbon::parse($appointment->appointment_date);

        $this->assertStringContainsString($date->locale('bn')->translatedFormat('F'), $rendered);
        $this->assertStringNotContainsString($date->locale('en')->translatedFormat('F'), $rendered);
    }

    public function test_a_broken_mail_server_does_not_break_the_booking(): void
    {
        // The booking is what matters. A mail failure is logged and swallowed;
        // turning it into a 500 would lose a patient's appointment outright.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP unreachable'));
        Log::shouldReceive('warning')->atLeast()->once();

        $this->post(route('appointment.store'), $this->publicBooking())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Appointment::count());
    }
}
