<?php

namespace Tests\Feature;

use App\Jobs\SendSms;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppointmentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        // Mail is faked so it never reaches the queue; Queue then sees only SMS.
        Mail::fake();
        Queue::fake();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);
        Setting::create(['key' => 'appointment_number', 'value' => '10666', 'group' => 'contact']);
        Setting::create(['key' => 'desk_sms_number', 'value' => '01999888777', 'group' => 'contact']);

        $department = Department::create([
            'name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse',
            'translations' => ['bn' => ['name' => 'কার্ডিওলজি']],
        ]);

        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'consultation_fee' => 2000,
            'is_active' => true,
            'accepts_online_appointment' => true,
            'translations' => ['bn' => ['name' => 'ডা. ফারহানা ইসলাম']],
        ]);

        $monday = Carbon::today()->next(Carbon::MONDAY);
        $this->date = $monday->toDateString();

        $this->doctor->schedules()->create([
            'day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '20:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);
    }

    private function booking(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $this->date,
            'appointment_time' => '17:20',
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'visit_type' => 'new',
        ], $overrides);
    }

    /** @return list<SendSms> */
    private function queuedTo(string $number): array
    {
        return Queue::pushed(SendSms::class, fn (SendSms $job) => $job->to === $number)->values()->all();
    }

    public function test_the_patient_is_texted_when_they_book(): void
    {
        $this->post(route('appointment.store'), $this->booking())->assertSessionHasNoErrors();

        // Stored as typed, sent as the gateway wants it.
        $messages = $this->queuedTo('8801712345678');

        $this->assertCount(1, $messages);
        $this->assertStringContainsString(Appointment::sole()->reference, $messages[0]->text);
        $this->assertStringContainsString('Dr. Farhana Islam', $messages[0]->text);
    }

    public function test_a_website_booking_says_it_is_not_confirmed_yet(): void
    {
        // Website bookings start as pending, and the SMS has to say so —
        // a patient who reads "confirmed" turns up to an unbooked slot.
        $this->post(route('appointment.store'), $this->booking());

        $this->assertStringContainsString('confirm shortly', $this->queuedTo('8801712345678')[0]->text);
    }

    public function test_the_desk_is_texted_about_a_website_booking(): void
    {
        $this->post(route('appointment.store'), $this->booking());

        $messages = $this->queuedTo('8801999888777');

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Rahim Uddin', $messages[0]->text);
        $this->assertStringContainsString('01712345678', $messages[0]->text);
    }

    public function test_no_desk_text_goes_out_when_the_number_is_unset(): void
    {
        Setting::where('key', 'desk_sms_number')->update(['value' => '']);
        Setting::flushCache();

        $this->post(route('appointment.store'), $this->booking());

        Queue::assertPushed(SendSms::class, 1);
    }

    public function test_a_landline_desk_number_is_ignored(): void
    {
        // A corporate 96xx line cannot receive an SMS however valid it looks,
        // and trying would fail once per booking, forever.
        Setting::where('key', 'desk_sms_number')->update(['value' => '+880 9612 345 610']);
        Setting::flushCache();

        $this->post(route('appointment.store'), $this->booking());

        Queue::assertPushed(SendSms::class, 1);
        $this->assertSame([], $this->queuedTo('8809612345610'));
    }

    public function test_a_front_desk_booking_texts_the_patient_but_not_the_desk(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.appointments.store'), [
                'doctor_id' => $this->doctor->id,
                'patient_name' => 'Karim Mia',
                'phone' => '01812345678',
                'appointment_date' => $this->date,
                'appointment_time' => '18:00',
                'visit_type' => 'new',
                'status' => 'confirmed',
                'locale' => 'en',
            ])->assertSessionHasNoErrors();

        $messages = $this->queuedTo('8801812345678');

        $this->assertCount(1, $messages);
        // Taken by the desk and confirmed on the spot, so it says confirmed.
        $this->assertStringContainsString('confirmed', $messages[0]->text);
        $this->assertSame([], $this->queuedTo('8801999888777'));
    }

    public function test_confirming_texts_the_patient(): void
    {
        $this->post(route('appointment.store'), $this->booking());
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', Appointment::sole()), ['status' => 'confirmed']);

        $this->assertStringContainsString('confirmed', $this->queuedTo('8801712345678')[0]->text);
    }

    public function test_cancelling_texts_the_patient(): void
    {
        // The one message a patient must not miss: without it they travel to
        // an appointment that is not there.
        $this->post(route('appointment.store'), $this->booking());
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', Appointment::sole()), ['status' => 'cancelled']);

        $this->assertStringContainsString('cancelled', $this->queuedTo('8801712345678')[0]->text);
    }

    public function test_internal_status_moves_send_nothing(): void
    {
        $this->post(route('appointment.store'), $this->booking());
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', Appointment::sole()), ['status' => 'completed']);

        Queue::assertNothingPushed();
    }

    public function test_a_bangla_patient_is_texted_in_bangla(): void
    {
        $this->withSession(['locale' => 'bn'])->post(route('appointment.store'), $this->booking());

        $text = $this->queuedTo('8801712345678')[0]->text;

        $this->assertStringContainsString('ডা. ফারহানা ইসলাম', $text);
        $this->assertStringNotContainsString('Dr. Farhana Islam', $text);
    }

    public function test_the_language_survives_a_staff_member_working_in_the_other_one(): void
    {
        $this->withSession(['locale' => 'bn'])->post(route('appointment.store'), $this->booking());
        Queue::fake();

        // An English session confirms it…
        $this->actingAs(User::factory()->create())
            ->patch(route('admin.appointments.status', Appointment::sole()), ['status' => 'confirmed']);

        // …and the patient is still texted in Bangla.
        $this->assertStringContainsString('নিশ্চিত', $this->queuedTo('8801712345678')[0]->text);
    }

    public function test_rendering_the_message_does_not_leak_the_locale(): void
    {
        // The notifier moves the app and Carbon locale to build the text; if it
        // failed to put them back, the response after a Bangla booking would
        // render in Bangla for an English visitor.
        app()->setLocale('en');
        Carbon::setLocale('en');

        $appointment = Appointment::create([
            'reference' => 'RBR000001',
            'doctor_id' => $this->doctor->id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => $this->date,
            'appointment_time' => '17:20',
            'locale' => 'bn',
        ]);

        app(AppointmentNotifier::class)->booked($appointment);

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', Carbon::getLocale());
    }
}
