<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * What was sent, to whom, and whether it actually went.
 *
 * Until this existed the answer to "did the patient get told?" was `reminded_at`
 * and a hope: a queued SMS the gateway accepted was as far as the system knew,
 * and a queue worker that was not running lost every message in silence while
 * every booking still succeeded.
 *
 * A row is written when a message is **queued** and updated when the transport
 * actually took it. That distinction is the point:
 *
 * - `queued` — dispatched, and nothing has confirmed it since. On a machine
 *   with no queue worker running, every row stays here forever. That is the
 *   symptom the deployment notes warn about, made visible.
 * - `sent` — the gateway or the mail server accepted it. Not proof of
 *   delivery: no gateway here reports back, and nothing pretends otherwise.
 * - `failed` — the SMS gateway refused it after its retries. Mail has no
 *   equivalent, because a queued mailable that gives up throws inside a
 *   framework job with nothing to correlate against; those stay `queued`.
 *
 * The body of an SMS is stored verbatim — it is the record of what was said,
 * and it is 160 characters at worst. An email's is not; its subject is.
 */
class NotificationLog extends Model
{
    use Prunable;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /**
     * Kept for three months. Long enough to answer "was I told?" about a visit
     * that has already happened, short enough that a table of every message
     * ever sent does not become the largest thing in the database.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(90));
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record a message on its way out.
     *
     * Never throws. This sits in the same paths as the notifier itself, where
     * a booking is the thing that matters and a logging table having a bad
     * afternoon must not turn a successful booking into a 500.
     */
    public static function queued(
        string $channel,
        string $type,
        string $recipient,
        string $locale,
        ?Model $related = null,
        ?string $body = null,
    ): ?self {
        try {
            return static::create([
                // Stated rather than left to the column default: a model
                // created here does not carry the database's defaults back,
                // and `$log->status` would read null on the way out.
                'status' => 'queued',
                'channel' => $channel,
                'type' => $type,
                'recipient' => $recipient,
                'locale' => $locale,
                'body' => $body,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function markSent(?int $id, ?string $subject = null): void
    {
        if ($id === null) {
            return;
        }

        static::whereKey($id)->update(array_filter([
            'status' => 'sent',
            'sent_at' => now(),
            'subject' => $subject,
            'updated_at' => now(),
        ], fn ($value) => $value !== null));
    }

    public static function markFailed(?int $id, string $error): void
    {
        if ($id === null) {
            return;
        }

        static::whereKey($id)->update([
            'status' => 'failed',
            // Truncated: a gateway that answers with an HTML error page should
            // not put a kilobyte of markup in every row of this table.
            'error' => mb_substr($error, 0, 500),
            'updated_at' => now(),
        ]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('id');
    }

    /** Queued a while ago and never confirmed — the shape of a dead worker. */
    public function scopeStuck(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('status', 'queued')
            ->where('created_at', '<', now()->subMinutes($minutes));
    }
}
