<?php

namespace App\Sms;

use App\Sms\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * One HTTP call to whatever gateway the hospital buys from.
 *
 * The parameter names are configuration rather than code because the local
 * providers differ only in what they call things — api_key vs token, msg vs
 * message, to vs mobile. See config/sms.php for the SMS_PARAMS syntax.
 */
class HttpGateway implements SmsGateway
{
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $text): void
    {
        $url = $this->config['url'] ?? null;

        if (blank($url)) {
            throw new SmsDeliveryException('No SMS_URL is configured for the http driver.');
        }

        $parameters = $this->interpolate($this->config['params'] ?? '', $to, $text);
        $headers = $this->interpolate($this->config['headers'] ?? '', $to, $text);
        $method = Str::lower($this->config['method'] ?? 'GET') === 'post' ? 'post' : 'get';

        $request = Http::timeout((int) ($this->config['timeout'] ?? 10))
            ->withHeaders($headers)
            ->acceptJson();

        if ($method === 'post' && ($this->config['json'] ?? false)) {
            $request = $request->asJson();
        }

        try {
            $response = $request->{$method}($url, $parameters);
        } catch (\Throwable $e) {
            throw new SmsDeliveryException("The SMS gateway could not be reached: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new SmsDeliveryException("The SMS gateway returned HTTP {$response->status()}.");
        }

        // Local gateways routinely answer 200 OK with an error in the body, so
        // a status code alone is not evidence the message went anywhere.
        $marker = $this->config['success'] ?? null;

        if (filled($marker) && ! Str::contains($response->body(), $marker, ignoreCase: true)) {
            throw new SmsDeliveryException('The SMS gateway rejected the message: '.Str::limit($response->body(), 200));
        }
    }

    /**
     * Turn "api_key=:key,to=:to,msg=:text" into a parameter array.
     *
     * @return array<string, string>
     */
    private function interpolate(?string $pairs, string $to, string $text): array
    {
        if (blank($pairs)) {
            return [];
        }

        $replacements = [
            ':key' => (string) ($this->config['key'] ?? ''),
            ':to' => $to,
            ':text' => $text,
            ':sender' => (string) config('sms.sender', ''),
        ];

        $parameters = [];

        foreach (explode(',', $pairs) as $pair) {
            if (! str_contains($pair, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $pair, 2);

            $parameters[trim($name)] = strtr(trim($value), $replacements);
        }

        return $parameters;
    }
}
