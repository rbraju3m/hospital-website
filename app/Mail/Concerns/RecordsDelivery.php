<?php

namespace App\Mail\Concerns;

use Illuminate\Mail\Mailables\Headers;

/**
 * Carries the notification_logs row id out to the mail server and back.
 *
 * A queued mailable is handed to a framework job, so there is nothing to hold
 * a reference on: the id rides along as a header, and `RecordMailDelivery`
 * reads it off the message once the transport has accepted it. The header is
 * ours, `X-`-prefixed, and goes out with the mail — visible in the raw source
 * of a received message, which is the same place a Message-ID is.
 */
trait RecordsDelivery
{
    public ?int $notificationLogId = null;

    public function recordAs(?int $id): static
    {
        $this->notificationLogId = $id;

        return $this;
    }

    public function headers(): Headers
    {
        return new Headers(text: array_filter([
            'X-Notification-Log' => $this->notificationLogId ? (string) $this->notificationLogId : null,
        ]));
    }
}
