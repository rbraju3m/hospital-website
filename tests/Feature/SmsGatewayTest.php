<?php

namespace Tests\Feature;

use App\Jobs\SendSms;
use App\Sms\Contracts\SmsGateway;
use App\Sms\DiscardGateway;
use App\Sms\HttpGateway;
use App\Sms\LogGateway;
use App\Sms\PhoneNumber;
use App\Sms\SmsDeliveryException;
use App\Sms\SmsManager;
use App\Sms\SmsText;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsGatewayTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            'as typed on the form' => ['01712345678', '8801712345678', true],
            'with a country code' => ['+8801712345678', '8801712345678', true],
            'country code, no plus' => ['8801712345678', '8801712345678', true],
            'spaced out' => ['+880 1712 345 678', '8801712345678', true],
            // A corporate line looks valid and cannot receive an SMS.
            'hospital landline' => ['+880 9612 345 610', '8809612345610', false],
            'short code' => ['10666', '88010666', false],
        ];
    }

    #[DataProvider('numbers')]
    public function test_numbers_normalise_for_the_gateway(string $input, string $expected, bool $mobile): void
    {
        $this->assertSame($expected, PhoneNumber::forGateway($input));
        $this->assertSame($mobile, PhoneNumber::isMobile($input));
    }

    public function test_a_blank_number_normalises_to_nothing(): void
    {
        $this->assertNull(PhoneNumber::forGateway(null));
        $this->assertNull(PhoneNumber::forGateway(''));
        $this->assertFalse(PhoneNumber::isMobile(null));
    }

    public function test_segments_follow_the_encoding(): void
    {
        // Latin fits 160 per segment; anything Bangla drops the whole message
        // into UCS-2, where a segment is 70.
        $this->assertFalse(SmsText::isUnicode('Appointment RBR260823 confirmed.'));
        $this->assertSame(1, SmsText::segments(str_repeat('a', 160)));
        $this->assertSame(2, SmsText::segments(str_repeat('a', 161)));

        $this->assertTrue(SmsText::isUnicode('অ্যাপয়েন্টমেন্ট নিশ্চিত'));
        $this->assertSame(1, SmsText::segments(str_repeat('আ', 70)));
        $this->assertSame(2, SmsText::segments(str_repeat('আ', 71)));
    }

    public function test_the_http_driver_builds_the_request_the_gateway_expects(): void
    {
        Http::fake(['*' => Http::response('SMS SUBMITTED SUCCESSFULLY')]);

        $gateway = new HttpGateway([
            'url' => 'https://api.example.test/send',
            'method' => 'GET',
            'params' => 'api_key=:key,to=:to,msg=:text,sender_id=:sender',
            'key' => 'secret-key',
            'success' => 'SUBMITTED',
        ]);

        config(['sms.sender' => 'RBRHOSP']);

        $gateway->send('8801712345678', 'Your appointment is confirmed.');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.example.test/send')
            && $request['api_key'] === 'secret-key'
            && $request['to'] === '8801712345678'
            && $request['msg'] === 'Your appointment is confirmed.'
            && $request['sender_id'] === 'RBRHOSP');
    }

    public function test_the_http_driver_can_post_json(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok'])]);

        (new HttpGateway([
            'url' => 'https://api.example.test/send',
            'method' => 'POST',
            'json' => true,
            'params' => 'token=:key,mobile=:to,message=:text',
            'key' => 'secret-key',
        ]))->send('8801712345678', 'Hello');

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['mobile'] === '8801712345678');
    }

    public function test_a_gateway_error_in_a_200_response_is_still_a_failure(): void
    {
        // Local gateways routinely answer 200 OK with the failure in the body,
        // so a status code alone proves nothing.
        Http::fake(['*' => Http::response('ERROR: invalid api key')]);

        $this->expectException(SmsDeliveryException::class);

        (new HttpGateway([
            'url' => 'https://api.example.test/send',
            'params' => 'api_key=:key,to=:to,msg=:text',
            'success' => 'SUBMITTED',
        ]))->send('8801712345678', 'Hello');
    }

    public function test_an_http_error_is_a_failure(): void
    {
        Http::fake(['*' => Http::response('nope', 503)]);

        $this->expectException(SmsDeliveryException::class);

        (new HttpGateway(['url' => 'https://api.example.test/send']))->send('8801712345678', 'Hello');
    }

    public function test_an_unconfigured_url_fails_loudly(): void
    {
        // Better a failed job somebody can see than silent success.
        $this->expectException(SmsDeliveryException::class);

        (new HttpGateway([]))->send('8801712345678', 'Hello');
    }

    public function test_the_manager_resolves_the_configured_driver(): void
    {
        config(['sms.default' => 'discard']);
        $this->assertInstanceOf(DiscardGateway::class, (new SmsManager)->driver());

        config(['sms.default' => 'log']);
        $this->assertInstanceOf(LogGateway::class, (new SmsManager)->driver());
    }

    public function test_an_unknown_driver_is_refused(): void
    {
        config(['sms.default' => 'carrier-pigeon']);

        $this->expectException(\InvalidArgumentException::class);

        (new SmsManager)->driver();
    }

    public function test_an_over_long_message_is_flagged_before_it_is_sent(): void
    {
        // Every segment is billed, so a template that quietly grew should not
        // do so silently.
        config(['sms.segment_warning' => 1]);
        Log::shouldReceive('warning')->once();

        $job = new SendSms('8801712345678', str_repeat('a', 400));
        $job->handle($this->app->make(SmsGateway::class));
    }

    /**
     * Every template, in every locale, rendered with values as long as they
     * realistically get.
     *
     * Operators bill per segment and Bangla costs two to three times what the
     * same message costs in English, so a template that quietly grows is a
     * recurring bill nobody notices. This fails the moment one does.
     */
    public function test_every_template_stays_within_its_segment_budget(): void
    {
        $budget = (int) config('sms.segment_warning', 3);

        $values = [
            'hospital' => 'RBR Hospital',
            'reference' => 'RBR260823XH7P',
            // One of the longer names in the seeded data.
            'doctor' => 'Prof. Dr. Ashraful Haque',
            'date' => '23 Aug',
            'time' => '5:40 PM',
            'phone' => '+880 9612 345 610',
            'patient' => 'Mohammad Rahim Uddin',
            'contact' => '01712345678',
        ];

        $report = [];

        foreach (array_keys(config('app.available_locales')) as $locale) {
            foreach (array_keys(trans('sms', [], $locale)) as $template) {
                $text = trans("sms.{$template}", $values, $locale);
                $segments = SmsText::segments($text);

                $report[] = sprintf('%s/%s: %d segment(s), %d chars', $locale, $template, $segments, mb_strlen($text));

                $this->assertLessThanOrEqual(
                    $budget,
                    $segments,
                    "sms.{$template} in [{$locale}] needs {$segments} segments, over the budget of {$budget}: {$text}"
                );

                // A placeholder that never got replaced would sail through the
                // length check while reaching the patient as ":doctor". A bare
                // colon is fine — the templates use one after the hospital name.
                foreach (array_keys($values) as $placeholder) {
                    $this->assertStringNotContainsString(":{$placeholder}", $text);
                }

                // Length is only half of it. One character outside the GSM
                // alphabet — an em dash, a curly quote, an ellipsis — switches
                // the whole message to UCS-2 and triples what it costs. An em
                // dash in the English reminder did exactly that.
                if ($locale === config('app.fallback_locale')) {
                    $this->assertFalse(
                        SmsText::isUnicode($text),
                        "sms.{$template} in [{$locale}] contains a character outside the GSM alphabet, "
                        ."which forces the whole message to UCS-2: {$text}"
                    );
                }
            }
        }

        $this->assertNotEmpty($report);
    }
}
