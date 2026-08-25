<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| The evening before, in Dhaka — app.timezone is Asia/Dhaka, so this is 6pm
| local and "tomorrow" means tomorrow here.
|
| 6pm rather than the small hours on purpose: an SMS that lands overnight is
| read in the morning at best and resented at worst, and a patient who cannot
| make it still has the evening to call the desk.
|
| A booking made after this runs gets no reminder — they only just booked, so
| they know. withoutOverlapping() guards against a slow run being restarted on
| top of itself.
*/
Schedule::command('appointments:remind')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->runInBackground();

/*
| The notification log keeps 90 days (NotificationLog::prunable) — long enough
| to answer "was I told?" about a visit that has already happened, short enough
| that a row per message ever sent does not become the largest table here.
|
| 3am rather than with the reminder: it deletes rows, and there is no reason for
| it to be running while the evening's messages are being written.
*/
Schedule::command('model:prune', ['--model' => [\App\Models\NotificationLog::class]])
    ->dailyAt('03:00')
    ->withoutOverlapping();
