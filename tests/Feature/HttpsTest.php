<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\Https;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * HTTPS is one switch — the scheme of APP_URL — and these are the two things
 * that go wrong when the halves disagree: a signed link signed as one scheme
 * and checked as the other, and a session cookie that is not marked Secure.
 */
class HttpsTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(): Appointment
    {
        $this->seed(DatabaseSeeder::class);
        $doctor = Doctor::firstOrFail();

        return Appointment::create([
            'reference' => 'RBR-HTTPS-1',
            'doctor_id' => $doctor->id,
            'department_id' => $doctor->department_id,
            'patient_name' => 'Rafiqul Islam',
            'phone' => '01712345678',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'visit_type' => 'new',
        ]);
    }

    public function test_the_app_url_scheme_is_what_decides(): void
    {
        $this->assertTrue(Https::wanted('https://rbrhospital.example'));
        $this->assertTrue(Https::wanted('HTTPS://RBRHOSPITAL.EXAMPLE'));
        $this->assertTrue(Https::wanted('  https://rbrhospital.example  '));

        $this->assertFalse(Https::wanted('http://hospital.local'));
        $this->assertFalse(Https::wanted(''));
        $this->assertFalse(Https::wanted(null));
    }

    public function test_an_https_app_url_makes_generated_links_https(): void
    {
        $this->assertTrue(Https::enforce('https://rbrhospital.example'));

        $this->assertStringStartsWith('https://', route('home'));
    }

    public function test_an_http_app_url_is_left_alone(): void
    {
        $this->assertFalse(Https::enforce('http://hospital.local'));
    }

    /**
     * The bug this exists to prevent. `appointments:remind` builds the
     * confirmation link under cron, with no request to take a scheme from, so
     * it uses APP_URL. If that says http and the web server redirects the
     * patient to https, the signature is checked against a URL that was never
     * signed — and the patient is shown a 403 for a booking that is perfectly
     * fine.
     */
    public function test_a_link_signed_as_https_is_refused_when_it_arrives_as_http(): void
    {
        $appointment = $this->appointment();

        Https::enforce('https://rbrhospital.example');
        $link = URL::signedRoute('appointment.confirmed', $appointment);
        $this->assertStringStartsWith('https://', $link);

        $this->get(str_replace('https://', 'http://', $link))->assertForbidden();
    }

    public function test_the_same_link_is_accepted_when_it_arrives_as_https(): void
    {
        $appointment = $this->appointment();

        Https::enforce('https://rbrhospital.example');

        $this->get(URL::signedRoute('appointment.confirmed', $appointment))->assertOk();
    }

    /**
     * Apache terminates TLS itself in deploy/, so nothing is in front and
     * nothing should be believed about X-Forwarded-Proto. Trusting every proxy
     * would let any client claim its plain-http request was secure.
     */
    public function test_no_proxy_is_trusted_unless_one_is_declared(): void
    {
        $this->assertNull(config('trustedproxy.proxies'));
    }

    /**
     * One switch, not two that can disagree: a patient's portal session cookie
     * is marked Secure because APP_URL says https, with nothing else to set.
     */
    public function test_the_session_cookie_follows_the_app_url_scheme(): void
    {
        $original = env('APP_URL');

        try {
            foreach (['https://rbrhospital.example' => true, 'http://hospital.local' => false] as $url => $expected) {
                putenv("APP_URL={$url}");
                $_ENV['APP_URL'] = $url;
                $_SERVER['APP_URL'] = $url;

                $this->assertSame($expected, (require config_path('session.php'))['secure']);
            }
        } finally {
            putenv('APP_URL='.$original);
            $_ENV['APP_URL'] = $original;
            $_SERVER['APP_URL'] = $original;
        }
    }
}
