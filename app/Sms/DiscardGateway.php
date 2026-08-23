<?php

namespace App\Sms;

use App\Sms\Contracts\SmsGateway;

/**
 * Discards everything. For tests, and for a staging site that must stay quiet.
 *
 * Named `discard` rather than the conventional `null` on purpose: dotenv reads
 * the literal string "null" as PHP null, so SMS_DRIVER=null would resolve to
 * no driver at all rather than to this one.
 */
class DiscardGateway implements SmsGateway
{
    public function send(string $to, string $text): void {}
}
