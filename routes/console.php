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
