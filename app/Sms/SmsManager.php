<?php

namespace App\Sms;

use App\Sms\Contracts\SmsGateway;
use InvalidArgumentException;

/**
 * Resolves the configured gateway. Kept explicit rather than extending
 * Illuminate\Support\Manager so that `send()` stays a real typed method
 * instead of arriving through __call.
 */
class SmsManager implements SmsGateway
{
    /** @var array<string, SmsGateway> */
    private array $resolved = [];

    public function send(string $to, string $text): void
    {
        $this->driver()->send($to, $text);
    }

    public function driver(?string $name = null): SmsGateway
    {
        $name ??= (string) config('sms.default', 'log');

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    private function resolve(string $name): SmsGateway
    {
        $config = config("sms.drivers.{$name}", []);

        return match ($name) {
            'log' => new LogGateway($config['channel'] ?? null),
            'discard' => new DiscardGateway,
            'http' => new HttpGateway($config),
            default => throw new InvalidArgumentException("No SMS driver [{$name}] is configured."),
        };
    }
}
