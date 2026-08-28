<?php

namespace Tests\Feature\Portal;

use App\Jobs\SendSms;
use App\Models\Patient;
use App\Models\PatientPasswordReset;
use App\Models\Setting;
use App\Services\PasswordResetCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        Setting::create(['key' => 'site_name', 'value' => 'RBR Hospital', 'group' => 'general']);
        Setting::create(['key' => 'hotline', 'value' => '10666', 'group' => 'contact']);
    }

    private function patient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'password' => 'correct-horse-1',
        ], $overrides));
    }

    public static function guardedRoutes(): array
    {
        return [
            'dashboard' => ['portal.dashboard'],
            'appointments' => ['portal.appointments'],
            'documents' => ['portal.documents'],
            'profile' => ['portal.profile'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_a_guest_is_sent_to_the_portal_login_not_the_staff_one(string $route): void
    {
        // A patient told to sign in at /admin would be asking IT for an
        // account that does not exist.
        $this->get(route($route))->assertRedirect(route('portal.login'));
    }

    public function test_registering_creates_an_account_and_signs_in(): void
    {
        $this->post(route('portal.register.store'), [
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'password' => 'correct-horse-1',
            'password_confirmation' => 'correct-horse-1',
        ])->assertRedirect(route('portal.dashboard'));

        $patient = Patient::sole();

        // Stored in the national form so lookups are exact.
        $this->assertSame('1712345678', $patient->phone);
        $this->assertSame('01712345678', $patient->displayPhone());
        $this->assertTrue(Hash::check('correct-horse-1', $patient->password));
        $this->assertAuthenticatedAs($patient, 'patient');
    }

    public function test_the_same_number_written_differently_is_still_the_same_account(): void
    {
        $this->patient();

        // 01712345678 and +8801712345678 must not become two accounts looking
        // at the same appointments.
        $this->post(route('portal.register.store'), [
            'name' => 'Someone Else',
            'phone' => '+8801712345678',
            'password' => 'correct-horse-1',
            'password_confirmation' => 'correct-horse-1',
        ])->assertSessionHasErrors('phone_national');

        $this->assertSame(1, Patient::count());
    }

    public function test_signing_in_works_in_any_of_the_accepted_formats(): void
    {
        $patient = $this->patient();

        $this->post(route('portal.login.store'), [
            'phone' => '+8801712345678',
            'password' => 'correct-horse-1',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($patient, 'patient');
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $this->patient();

        $this->post(route('portal.login.store'), [
            'phone' => '01712345678',
            'password' => 'wrong',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest('patient');
    }

    public function test_a_disabled_account_cannot_sign_in(): void
    {
        // And fails exactly as a wrong password does, rather than announcing
        // that the account exists.
        $this->patient(['is_active' => false]);

        $this->post(route('portal.login.store'), [
            'phone' => '01712345678',
            'password' => 'correct-horse-1',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest('patient');
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $this->patient();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('portal.login.store'), ['phone' => '01712345678', 'password' => 'wrong']);
        }

        $this->post(route('portal.login.store'), ['phone' => '01712345678', 'password' => 'correct-horse-1'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest('patient');
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->actingAs($this->patient(), 'patient')
            ->post(route('portal.logout'))
            ->assertRedirect(route('portal.login'));

        $this->assertGuest('patient');
    }

    public function test_a_patient_cannot_reach_the_staff_panel(): void
    {
        // The two guards are separate on purpose: being signed in here is not
        // being signed in there.
        $this->actingAs($this->patient(), 'patient')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_a_reset_code_is_texted_to_the_registered_number(): void
    {
        $this->patient();

        $this->post(route('portal.password.send'), ['phone' => '01712345678'])
            ->assertSessionHas('status');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '8801712345678');
        $this->assertSame(1, PatientPasswordReset::count());
    }

    public function test_the_code_is_not_stored_in_the_clear(): void
    {
        // A leaked table must not hand anybody a working code.
        $this->patient();
        $this->post(route('portal.password.send'), ['phone' => '01712345678']);

        $code = $this->codeFromQueue();
        $reset = PatientPasswordReset::sole();

        $this->assertNotSame($code, $reset->code_hash);
        $this->assertTrue(Hash::check($code, $reset->code_hash));
    }

    public function test_an_unknown_number_gets_the_same_answer_and_no_sms(): void
    {
        // Whether a number has an account here is not something a stranger
        // gets to find out.
        $this->post(route('portal.password.send'), ['phone' => '01999888777'])
            ->assertSessionHas('status');

        Queue::assertNothingPushed();
        $this->assertSame(0, PatientPasswordReset::count());
    }

    public function test_a_valid_code_changes_the_password_once(): void
    {
        $patient = $this->patient();
        $this->post(route('portal.password.send'), ['phone' => '01712345678']);
        $code = $this->codeFromQueue();

        $this->post(route('portal.password.update'), [
            'phone' => '01712345678',
            'code' => $code,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertRedirect(route('portal.login'));

        $this->assertTrue(Hash::check('a-brand-new-one', $patient->fresh()->password));

        // The same code a second time is worthless.
        $this->post(route('portal.password.update'), [
            'phone' => '01712345678',
            'code' => $code,
            'password' => 'third-password-x',
            'password_confirmation' => 'third-password-x',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('a-brand-new-one', $patient->fresh()->password));
    }

    public function test_an_expired_code_is_refused(): void
    {
        $patient = $this->patient();
        $this->post(route('portal.password.send'), ['phone' => '01712345678']);
        $code = $this->codeFromQueue();

        $this->travel(PasswordResetCodes::TTL_MINUTES + 1)->minutes();

        $this->post(route('portal.password.update'), [
            'phone' => '01712345678',
            'code' => $code,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('correct-horse-1', $patient->fresh()->password));
    }

    public function test_a_code_gives_up_after_repeated_wrong_guesses(): void
    {
        // Six digits is a small space to search through in ten minutes.
        $patient = $this->patient();
        $this->post(route('portal.password.send'), ['phone' => '01712345678']);
        $code = $this->codeFromQueue();

        foreach (range(1, PasswordResetCodes::MAX_ATTEMPTS) as $attempt) {
            $this->post(route('portal.password.update'), [
                'phone' => '01712345678',
                'code' => '000000' === $code ? '111111' : '000000',
                'password' => 'a-brand-new-one',
                'password_confirmation' => 'a-brand-new-one',
            ]);
        }

        // Even the right code no longer works.
        $this->post(route('portal.password.update'), [
            'phone' => '01712345678',
            'code' => $code,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('correct-horse-1', $patient->fresh()->password));
    }

    public function test_asking_for_a_new_code_retires_the_old_one(): void
    {
        $this->patient();

        $this->post(route('portal.password.send'), ['phone' => '01712345678']);
        $first = $this->codeFromQueue();

        $this->post(route('portal.password.send'), ['phone' => '01712345678']);

        $this->post(route('portal.password.update'), [
            'phone' => '01712345678',
            'code' => $first,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('code');
    }

    /** Pull the plain code out of the queued SMS — the only place it exists. */
    private function codeFromQueue(): string
    {
        $job = Queue::pushed(SendSms::class)->last();

        preg_match('/\b(\d{6})\b/', $job->text, $matches);

        return $matches[1];
    }

    /**
     * The four screens a signed-out patient can reach, rendered. Every other
     * test in here posts to them, which never renders the form it is posting
     * to — and a component tag Blade cannot parse is left in the output
     * verbatim, so a required field simply is not on the page while the page
     * still answers 200. That shipped once already, on the panel's document
     * form. `reset-password` is the one to care about: it is how a patient who
     * has lost their password reaches their own reports, and until now neither
     * this suite nor the browser walk had ever rendered it.
     */
    public function test_every_signed_out_screen_renders(): void
    {
        $urls = [
            route('portal.login'),
            route('portal.register'),
            route('portal.password.request'),
            route('portal.password.reset'),
            route('portal.password.reset', ['phone' => '01712345678']),
        ];

        foreach ($urls as $url) {
            $content = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString(
                '<x-', $content, $url.' shipped a component tag Blade never compiled.',
            );
        }
    }
}
