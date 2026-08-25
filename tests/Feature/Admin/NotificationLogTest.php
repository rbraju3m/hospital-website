<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendSms;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\AppointmentNotifier;
use App\Sms\Contracts\SmsGateway;
use App\Sms\SmsDeliveryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The record of what was sent to whom.
 *
 * The distinction the whole table exists for is queued vs sent: a booking
 * succeeds either way, and on a machine with no queue worker running every
 * message is lost in silence. A row that never leaves `queued` is that
 * failure, written down.
 */
class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
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
            'email' => 'rahim@example.test',
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '17:20',
            'status' => 'confirmed',
            'locale' => 'bn',
        ], $overrides));
    }

    public function test_a_booking_writes_down_both_channels(): void
    {
        app(AppointmentNotifier::class)->booked($this->booking(), alertDesk: false);

        $sms = NotificationLog::where('channel', 'sms')->sole();
        $mail = NotificationLog::where('channel', 'mail')->sole();

        // The number as the gateway sees it, and the text verbatim — this is
        // the record of what was actually said.
        $this->assertSame('8801712345678', $sms->recipient);
        $this->assertSame('booked_confirmed', $sms->type);
        $this->assertStringContainsString('RBR7K2M9X', $sms->body);
        $this->assertSame('bn', $sms->locale);

        $this->assertSame('rahim@example.test', $mail->recipient);
        // An email's body is a page of HTML nobody would read here.
        $this->assertNull($mail->body);

        // Both point at the booking they are about.
        $this->assertTrue($sms->related->is($mail->related));
        $this->assertSame('RBR7K2M9X', $sms->related->reference);
    }

    public function test_the_desk_alert_is_logged_separately_from_the_patient(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'appointment_email'], ['value' => 'desk@rbr.test']);
        \App\Models\Setting::updateOrCreate(['key' => 'desk_sms_number'], ['value' => '01812345678']);

        app(AppointmentNotifier::class)->booked($this->booking());

        $this->assertSame(1, NotificationLog::where('type', 'desk_alert')->where('channel', 'mail')->count());
        $this->assertSame(1, NotificationLog::where('type', 'desk_alert')->where('channel', 'sms')->count());
        $this->assertSame('desk@rbr.test', NotificationLog::where('type', 'desk_alert')->where('channel', 'mail')->sole()->recipient);
    }

    public function test_the_sms_is_marked_sent_once_the_gateway_takes_it(): void
    {
        app(AppointmentNotifier::class)->booked($this->booking(), alertDesk: false);

        // QUEUE_CONNECTION is sync in the test suite, so the job has already
        // run by the time the notifier returns.
        $this->assertSame('sent', NotificationLog::where('channel', 'sms')->sole()->status);
        $this->assertNotNull(NotificationLog::where('channel', 'sms')->sole()->sent_at);
    }

    public function test_the_email_is_marked_sent_by_the_header_it_carried(): void
    {
        app(AppointmentNotifier::class)->booked($this->booking(), alertDesk: false);

        $mail = NotificationLog::where('channel', 'mail')->sole();

        $this->assertSame('sent', $mail->status);
        // The subject is read off the finished message, in the locale it was
        // rendered in, rather than guessed at when it was queued.
        $this->assertNotNull($mail->subject);
    }

    public function test_a_gateway_that_refuses_is_written_down_as_failed(): void
    {
        $log = NotificationLog::queued('sms', 'reminder', '8801712345678', 'en', $this->booking(), 'Text');

        (new SendSms('8801712345678', 'Text', $log->id))
            ->failed(new SmsDeliveryException('Gateway said: insufficient balance'));

        $log->refresh();

        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('insufficient balance', $log->error);
    }

    public function test_a_message_nobody_confirmed_stays_queued(): void
    {
        // What a machine with no queue worker looks like: the row is written,
        // and nothing ever comes back to say it went.
        $log = NotificationLog::queued('sms', 'reminder', '8801712345678', 'en', $this->booking(), 'Text');

        $this->assertSame('queued', $log->status);
        $this->assertNull($log->sent_at);

        $log->update(['created_at' => now()->subHour()]);

        $this->assertSame(1, NotificationLog::stuck()->count());
    }

    public function test_a_failure_to_log_never_costs_a_booking(): void
    {
        // Nothing in the notifier is allowed to throw, and that has to include
        // the thing that writes down what the notifier did.
        \Illuminate\Support\Facades\Schema::drop('notification_logs');

        app(AppointmentNotifier::class)->booked($this->booking(), alertDesk: false);

        $this->assertTrue(true, 'The booking survived a broken log table.');
    }

    public function test_the_reset_code_is_logged_without_the_code_in_it(): void
    {
        $patient = \App\Models\Patient::create([
            'name' => 'Shirin Akter',
            'phone' => '1812345678',
            'password' => \Illuminate\Support\Facades\Hash::make('secret-password'),
        ]);

        $this->post(route('portal.password.send'), ['phone' => '01812345678']);

        $log = NotificationLog::where('type', 'password_reset')->sole();

        // A six-digit code sitting in a panel listing is a way into somebody's
        // medical records.
        $this->assertNull($log->body);
        $this->assertSame('8801812345678', $log->recipient);
        $this->assertTrue($log->related->is($patient));
    }

    public function test_the_listing_filters_and_flags_what_is_stuck(): void
    {
        app(AppointmentNotifier::class)->booked($this->booking(), alertDesk: false);
        NotificationLog::query()->update(['status' => 'queued', 'created_at' => now()->subHour()]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('8801712345678')
            ->assertSee('rahim@example.test');

        // The band that says the queue worker is not running.
        $response->assertSee(trans_choice('admin.notifications.stuck_warning', 2, ['count' => 2]));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.notifications.index', ['channel' => 'sms']))
            ->assertOk()
            ->assertSee('8801712345678')
            ->assertDontSee('rahim@example.test');
    }

    public function test_only_the_desk_and_administrators_read_it(): void
    {
        $this->actingAs(User::factory()->frontDesk()->create())
            ->get(route('admin.notifications.index'))->assertOk();

        // It is a list of patients' numbers and what they were told.
        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.notifications.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.notifications.index'))->assertRedirect(route('admin.login'));
    }

    public function test_the_log_is_kept_for_ninety_days(): void
    {
        $old = NotificationLog::queued('sms', 'reminder', '8801712345678', 'en', null, 'Text');
        $old->update(['created_at' => now()->subDays(91)]);

        $recent = NotificationLog::queued('sms', 'reminder', '8801712345679', 'en', null, 'Text');

        $this->artisan('model:prune', ['--model' => [NotificationLog::class]])->assertSuccessful();

        $this->assertNull(NotificationLog::find($old->id));
        $this->assertNotNull(NotificationLog::find($recent->id));
    }
}
