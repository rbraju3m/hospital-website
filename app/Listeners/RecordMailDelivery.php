<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Marks a logged email as sent once the mail server has taken it.
 *
 * The correlation is the `X-Notification-Log` header the mailable carried out
 * (see `RecordsDelivery`), rather than matching on the address and the subject
 * — two reminders to the same patient on the same evening would be
 * indistinguishable that way.
 *
 * The subject is read off the finished message rather than stored at queue
 * time: it is built inside `envelope()`, in the recipient's locale, and the
 * point of this table is to record what was actually sent.
 */
class RecordMailDelivery
{
    public function handle(MessageSent $event): void
    {
        $header = $event->message->getHeaders()->get('X-Notification-Log');

        if ($header === null) {
            return;
        }

        NotificationLog::markSent(
            (int) $header->getBodyAsString(),
            $event->message->getSubject(),
        );
    }
}
