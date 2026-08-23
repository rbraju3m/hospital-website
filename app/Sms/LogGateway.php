<?php

namespace App\Sms;

use App\Sms\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Writes the message to the log instead of sending it. The default driver, so
 * a fresh checkout with no gateway credentials still exercises the whole path.
 */
class LogGateway implements SmsGateway
{
    public function __construct(private readonly ?string $channel = null) {}

    public function send(string $to, string $text): void
    {
        Log::channel($this->channel ?: config('logging.default'))->info('SMS', [
            'to' => $to,
            'segments' => SmsText::segments($text),
            'encoding' => SmsText::isUnicode($text) ? 'UCS-2' : 'GSM-7',
            'text' => $text,
        ]);
    }
}
