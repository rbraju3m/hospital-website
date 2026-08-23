<?php

namespace Tests\Feature;

use App\Jobs\SendSms;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);

        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'consultation_fee' => 2000,
            'translations' => ['bn' => ['name' => 'ডা. ফারহানা ইসলাম']],
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
            'email' => 'rahim@example.test',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            // A distinct slot each time — (doctor, date, time) is unique.
            'appointment_time' => sprintf('%02d:00:00', 8 + ($counter % 10)),
            'status' => 'confirmed',
            'locale' => 'en',
        ], $overrides));
    }

    public function test_tomorrows_confirmed_appointments_are_reminded(): void
    {
        $appointment = $this->appointment();

        $this->artisan('appointments:remind')->assertSuccessful();

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '8801712345678');
        Mail::assertQueued(AppointmentReminder::class, fn ($mail) => $mail->hasTo('rahim@example.test'));

        $this->assertNotNull($appointment->fresh()->reminded_at);
    }

    public function test_nobody_is_reminded_twice(): void
    {
        // Cron can double-fire and a failed run gets repeated by hand.
        $this->appointment();

        $this->artisan('appointments:remind');
        Queue::assertPushed(SendSms::class, 1);

        $this->artisan('appointments:remind')->assertSuccessful();
        Queue::assertPushed(SendSms::class, 1);
    }

    public function test_force_reminds_again(): void
    {
        // For the morning after a gateway outage.
        $this->appointment();

        $this->artisan('appointments:remind');
        $this->artisan('appointments:remind', ['--force' => true]);

        Queue::assertPushed(SendSms::class, 2);
    }

    public function test_an_unconfirmed_booking_is_reported_rather_than_reminded(): void
    {
        // Telling a patient to come tomorrow for a slot the desk never agreed
        // to is worse than saying nothing.
        $this->appointment(['status' => 'pending']);

        $this->artisan('appointments:remind')
            ->expectsOutputToContain('1 booking(s) for that date are still pending')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        Mail::assertNothingQueued();
    }

    public function test_cancelled_and_completed_appointments_are_left_alone(): void
    {
        $this->appointment(['status' => 'cancelled']);
        $this->appointment(['status' => 'completed']);

        $this->artisan('appointments:remind');

        Queue::assertNothingPushed();
    }

    public function test_only_tomorrow_is_reminded(): void
    {
        $today = $this->appointment(['appointment_date' => Carbon::today()->toDateString()]);
        $later = $this->appointment(['appointment_date' => Carbon::today()->addDays(2)->toDateString()]);
        $tomorrow = $this->appointment();

        $this->artisan('appointments:remind');

        $this->assertNull($today->fresh()->reminded_at);
        $this->assertNull($later->fresh()->reminded_at);
        $this->assertNotNull($tomorrow->fresh()->reminded_at);
    }

    public function test_a_specific_date_can_be_targeted(): void
    {
        $later = $this->appointment(['appointment_date' => Carbon::today()->addDays(5)->toDateString()]);

        $this->artisan('appointments:remind', ['--date' => Carbon::today()->addDays(5)->toDateString()])
            ->assertSuccessful();

        $this->assertNotNull($later->fresh()->reminded_at);
    }

    public function test_a_dry_run_sends_nothing_and_marks_nothing(): void
    {
        $appointment = $this->appointment();

        $this->artisan('appointments:remind', ['--dry-run' => true])
            ->expectsOutputToContain('would remind')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        Mail::assertNothingQueued();
        $this->assertNull($appointment->fresh()->reminded_at);
    }

    public function test_a_patient_without_an_email_still_gets_the_text(): void
    {
        // Email is optional on the booking form; phone is not.
        $this->appointment(['email' => null]);

        $this->artisan('appointments:remind');

        Queue::assertPushed(SendSms::class, 1);
        Mail::assertNothingQueued();
    }

    public function test_the_reminder_uses_the_language_the_patient_booked_in(): void
    {
        $this->appointment(['locale' => 'bn']);

        $this->artisan('appointments:remind');

        $job = Queue::pushed(SendSms::class)->first();

        $this->assertStringContainsString('ডা. ফারহানা ইসলাম', $job->text);
        $this->assertStringNotContainsString('Dr. Farhana Islam', $job->text);

        Mail::assertQueued(AppointmentReminder::class, fn ($mail) => $mail->locale === 'bn');
    }

    public function test_nothing_happens_on_a_quiet_day(): void
    {
        $this->artisan('appointments:remind')
            ->expectsOutputToContain('0 confirmed appointment(s)')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
