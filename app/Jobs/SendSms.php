<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Sms\Contracts\SmsGateway;
use App\Sms\SmsText;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * One message, sent off the request.
 *
 * A gateway that is slow, rate-limiting or briefly down must not hold up a
 * booking, and it is worth retrying — so this is a job rather than an inline
 * call. Exhausted retries land in `failed_jobs` where someone can see them.
 */
class SendSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Backing off matters here: local gateways rate-limit rather than fail. */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly string $to,
        public readonly string $text,
        /* The notification_logs row this message was written down as, so the
           worker can say whether it actually went. Nullable because a message
           sent from somewhere that has not been given a log is still a message
           that should be sent. */
        public readonly ?int $logId = null,
    ) {}

    public function handle(SmsGateway $gateway): void
    {
        $segments = SmsText::segments($this->text);

        if ($segments > (int) config('sms.segment_warning', 3)) {
            // Every segment is billed separately, so a template that quietly
            // grew is a recurring cost, not a one-off.
            Log::warning('An SMS template is longer than expected.', [
                'to' => $this->to,
                'segments' => $segments,
                'encoding' => SmsText::isUnicode($this->text) ? 'UCS-2' : 'GSM-7',
            ]);
        }

        $gateway->send($this->to, $this->text);

        NotificationLog::markSent($this->logId);
    }

    /**
     * After the last retry. This is the only place in the application that can
     * say an SMS definitively did not go — the gateway refused it three times
     * over five minutes — so it is worth writing down rather than leaving in
     * `failed_jobs` for somebody to find.
     */
    public function failed(?\Throwable $exception): void
    {
        NotificationLog::markFailed($this->logId, $exception?->getMessage() ?? 'unknown');
    }
}
