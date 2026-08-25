<?php

namespace Tests\Feature\Portal;

use App\Mail\AppointmentChangedAlert;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Support\SiteFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A patient moving or cancelling their own booking.
 *
 * The rules are the booking form's rules — the published grid, the window, the
 * lead time — because a slot reachable from here is one they could have booked
 * in the first place. What this file mostly guards is the other half: whose
 * booking it is, when it stops being changeable, and who gets told.
 */
class PortalAppointmentChangeTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Doctor $doctor;

    private string $monday;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);
        Setting::create(['key' => 'appointment_email', 'value' => 'desk@rbr.test', 'group' => 'contact']);
        Setting::create(['key' => 'desk_sms_number', 'value' => '01812345678', 'group' => 'contact']);

        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $this->doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
            'accepts_online_appointment' => true,
        ]);

        $this->doctor->schedules()->create([
            'day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '20:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);

        // A Monday inside the booking window, so the grid has real slots on it.
        $this->monday = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        $this->patient = Patient::create([
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'password' => 'correct-horse-1',
        ]);
    }

    private function booking(array $overrides = []): Appointment
    {
        static $counter = 0;
        $counter++;

        return Appointment::create(array_merge([
            'reference' => 'RBRP'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->doctor->department_id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => $this->monday,
            'appointment_time' => '17:00:00',
            'status' => 'confirmed',
            'locale' => 'en',
        ], $overrides));
    }

    public function test_a_patient_cancels_their_own_booking(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $booking))
            ->assertRedirect(route('portal.appointments'))
            ->assertSessionHas('status');

        $booking->refresh();

        $this->assertSame('cancelled', $booking->status);
        // Which of the two of us cancelled: the desk's next move depends on it.
        $this->assertSame('patient', $booking->cancelled_by);
    }

    public function test_cancelling_tells_the_desk_and_not_the_patient(): void
    {
        Mail::fake();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $this->booking()));

        Mail::assertQueued(AppointmentChangedAlert::class, fn ($mail) => $mail->hasTo('desk@rbr.test'));

        /* The patient is the one who just did it, and the portal has already
           said so on the screen in front of them — the mirror image of the desk
           getting no alert for a booking it took itself. */
        $this->assertSame(0, NotificationLog::where('recipient', '8801712345678')->count());
        $this->assertSame(1, NotificationLog::where('recipient', '8801812345678')->count());
    }

    public function test_a_patient_moves_their_own_booking(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.move', $booking), [
                'appointment_date' => $this->monday,
                'appointment_time' => '18:20',
            ])
            ->assertRedirect(route('portal.appointments'));

        $booking->refresh();

        $this->assertSame('18:20:00', $booking->appointment_time);
        $this->assertNotNull($booking->rescheduled_at);
        // The desk agreed to a time, and this is not that time.
        $this->assertSame('pending', $booking->status);
    }

    public function test_a_move_cannot_take_a_slot_that_is_gone(): void
    {
        $this->booking(['appointment_time' => '18:20:00']);
        $mine = $this->booking(['appointment_time' => '17:00:00']);

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.move', $mine), [
                'appointment_date' => $this->monday,
                'appointment_time' => '18:20',
            ])
            ->assertSessionHasErrors('appointment_time');

        $this->assertSame('17:00:00', $mine->fresh()->appointment_time);
    }

    public function test_a_move_must_land_on_the_published_grid(): void
    {
        $booking = $this->booking();

        // 21:00 is after the chamber closes. The portal runs on the same grid
        // as the booking form; a time nobody could book is not a time.
        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.move', $booking), [
                'appointment_date' => $this->monday,
                'appointment_time' => '21:00',
            ])
            ->assertSessionHasErrors('appointment_time');
    }

    public function test_a_move_stays_inside_the_booking_window(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.move', $booking), [
                'appointment_date' => Carbon::today()->addDays(60)->toDateString(),
                'appointment_time' => '17:00',
            ])
            ->assertSessionHasErrors('appointment_date');
    }

    public function test_somebody_elses_booking_is_not_found(): void
    {
        $theirs = $this->booking(['phone' => '01912345678', 'patient_name' => 'Someone Else']);

        // 404 rather than 403: a reference is short enough to guess at, and
        // "wrong but real" is worth more to somebody guessing than "not found".
        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $theirs))
            ->assertNotFound();

        $this->assertSame('confirmed', $theirs->fresh()->status);
    }

    #[DataProvider('phoneFormats')]
    public function test_the_booking_is_theirs_in_any_spelling_of_the_number(string $phone): void
    {
        $booking = $this->booking(['phone' => $phone]);

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $booking))
            ->assertRedirect(route('portal.appointments'));
    }

    public static function phoneFormats(): array
    {
        return [
            'as typed' => ['01712345678'],
            'with country code' => ['8801712345678'],
            'with a plus' => ['+8801712345678'],
        ];
    }

    public function test_a_visit_that_has_happened_cannot_be_changed(): void
    {
        $past = $this->booking(['appointment_date' => Carbon::yesterday()->toDateString()]);

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $past))
            ->assertForbidden();
    }

    #[DataProvider('closedStatuses')]
    public function test_a_booking_the_desk_has_closed_cannot_be_changed(string $status): void
    {
        $booking = $this->booking(['status' => $status]);

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $booking))
            ->assertForbidden();
    }

    public static function closedStatuses(): array
    {
        return ['completed' => ['completed'], 'cancelled' => ['cancelled']];
    }

    public function test_the_switch_closes_the_routes_as_well_as_the_buttons(): void
    {
        $booking = $this->booking();

        /* Everything as it is, minus this one switch. `store()` walks the
           registry rather than the payload — an unchecked box posts nothing —
           so a one-key call would switch off the whole site, portal included. */
        SiteFeatures::store(array_replace(
            SiteFeatures::all(),
            ['behaviour_portal_changes' => false],
        ));

        // Hiding the button alone would leave a bookmarked page as a way round
        // the decision — the same rule every other Site control follows.
        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.appointments.reschedule', $booking))
            ->assertNotFound();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $booking))
            ->assertNotFound();

        // The buttons go with the routes. Asserted on the action URL rather
        // than the word "Change", which the language switcher in the header
        // also uses.
        $this->actingAs($this->patient, 'patient')
            ->get(route('portal.appointments'))
            ->assertOk()
            ->assertDontSee(route('portal.appointments.cancel', $booking))
            ->assertSee(__('portal.appointments.change_note_off', ['phone' => '10666']));
    }

    public function test_the_buttons_are_only_on_bookings_that_can_still_change(): void
    {
        $this->booking(['reference' => 'RBRLIVE01']);
        $this->booking(['reference' => 'RBRDONE01', 'appointment_date' => Carbon::yesterday()->toDateString()]);

        $html = $this->actingAs($this->patient, 'patient')
            ->get(route('portal.appointments'))->assertOk()->getContent();

        $this->assertStringContainsString(route('portal.appointments.cancel', 'RBRLIVE01'), $html);
        $this->assertStringNotContainsString(route('portal.appointments.cancel', 'RBRDONE01'), $html);
    }

    public function test_the_slot_endpoint_only_answers_for_your_own_booking(): void
    {
        $theirs = $this->booking(['phone' => '01912345678']);

        $this->actingAs($this->patient, 'patient')
            ->getJson(route('portal.appointments.slots', $theirs).'?date='.$this->monday)
            ->assertNotFound();

        $mine = $this->booking(['appointment_time' => '17:20:00']);

        $this->actingAs($this->patient, 'patient')
            ->getJson(route('portal.appointments.slots', $mine).'?date='.$this->monday)
            ->assertOk()
            ->assertJsonStructure(['date', 'slots']);
    }

    public function test_a_guest_cannot_change_anything(): void
    {
        $booking = $this->booking();

        $this->patch(route('portal.appointments.cancel', $booking))->assertRedirect(route('portal.login'));
    }

    public function test_the_panel_says_the_patient_did_it(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->patient, 'patient')
            ->patch(route('portal.appointments.cancel', $booking));

        // `actingAs` with no guard uses the *default*, which the patient sign-in
        // above moved. The panel is on the web guard and has to be named.
        $this->actingAs(\App\Models\User::factory()->create(), 'web')
            ->get(route('admin.appointments.show', $booking->fresh()))
            ->assertOk()
            ->assertSee(__('admin.appointments.cancelled_by_patient'));
    }
}
