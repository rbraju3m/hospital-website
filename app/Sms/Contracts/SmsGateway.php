<?php

namespace App\Sms\Contracts;

interface SmsGateway
{
    /**
     * Deliver one message.
     *
     * Implementations throw on failure rather than returning false — the send
     * happens inside a queued job, and a thrown exception is what makes the
     * queue retry it and eventually record it in `failed_jobs`.
     *
     * @param  string  $to  A number already normalised by PhoneNumber::forGateway()
     *
     * @throws \App\Sms\SmsDeliveryException
     */
    public function send(string $to, string $text): void;
}
