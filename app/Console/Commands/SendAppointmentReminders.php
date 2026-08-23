<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The day-before reminder, run once a day by the scheduler.
 *
 * Only **confirmed** appointments are reminded. A booking still sitting at
 * `pending` is one the desk has not agreed to yet, and telling a patient to
 * come tomorrow for a slot nobody secured is worse than saying nothing — so
 * those are counted and reported instead, where the desk can act on them.
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:remind
                            {--date= : The appointment date to remind about (Y-m-d), defaults to tomorrow}
                            {--dry-run : List who would be reminded without sending anything}
                            {--force : Remind again even if a reminder already went out}';

    protected $description = 'Remind patients about tomorrow’s appointments by SMS and email';

    public function handle(AppointmentNotifier $notifier): int
    {
        $date = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'))
            : CarbonImmutable::tomorrow();

        $due = Appointment::with(['doctor.department', 'department'])
            ->whereDate('appointment_date', $date)
            ->where('status', 'confirmed')
            // Idempotence: cron can double-fire and a failed run gets repeated
            // by hand. Nobody should be reminded twice.
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('reminded_at'))
            ->orderBy('appointment_time')
            ->get();

        $unconfirmed = Appointment::whereDate('appointment_date', $date)
            ->where('status', 'pending')
            ->count();

        $this->components->info(sprintf(
            '%s: %d confirmed appointment(s) to remind.',
            $date->toDateString(),
            $due->count()
        ));

        if ($unconfirmed > 0) {
            $this->components->warn(
                "{$unconfirmed} booking(s) for that date are still pending and were not reminded."
            );
        }

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $appointment) {
            $line = sprintf(
                '%s  %-13s %-22s %s',
                $appointment->formattedTime(),
                $appointment->reference,
                mb_strimwidth($appointment->patient_name, 0, 22, '…'),
                $appointment->phone
            );

            if ($this->option('dry-run')) {
                $this->line("  would remind  {$line}");

                continue;
            }

            $notifier->reminder($appointment);

            // Written even when a channel fails: the notifier swallows failures
            // by design, and once a job is queued the queue owns the retries.
            $appointment->forceFill(['reminded_at' => now()])->save();

            $this->line("  reminded  {$line}");
        }

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run — nothing was sent and nothing was marked as reminded.');
        }

        return self::SUCCESS;
    }
}
