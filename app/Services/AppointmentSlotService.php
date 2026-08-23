<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Turns a doctor's weekly chamber schedule into concrete, bookable time slots
 * for a given date, minus anything already taken.
 */
class AppointmentSlotService
{
    /** How far ahead the public site allows booking. */
    public const BOOKING_WINDOW_DAYS = 30;

    /** Minimum lead time before a slot on today's date can be booked. */
    public const MIN_LEAD_MINUTES = 60;

    /**
     * Bookable slots for one doctor on one date.
     *
     * @return Collection<int, array{time: string, label: string, location: ?string}>
     */
    public function slotsFor(Doctor $doctor, CarbonImmutable $date): Collection
    {
        if (! $this->isDateBookable($date)) {
            return collect();
        }

        $schedules = $doctor->schedules()
            ->where('is_active', true)
            ->where('day_of_week', $date->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $taken = $this->takenSlotCounts($doctor, $date);
        $earliest = $date->isToday()
            ? CarbonImmutable::now()->addMinutes(self::MIN_LEAD_MINUTES)
            : null;

        return $schedules->flatMap(function ($schedule) use ($date, $taken, $earliest) {
            $slots = [];
            $cursor = $this->at($date, $schedule->start_time);
            $end = $this->at($date, $schedule->end_time);
            $step = max(5, (int) $schedule->slot_minutes);

            while ($cursor->lt($end)) {
                $key = $cursor->format('H:i:s');
                $booked = $taken[$key] ?? 0;
                $isPast = $earliest && $cursor->lt($earliest);

                if (! $isPast && $booked < $schedule->capacity_per_slot) {
                    $slots[] = [
                        'time' => $cursor->format('H:i'),
                        'label' => $cursor->format('g:i A'),
                        'location' => $schedule->location,
                    ];
                }

                $cursor = $cursor->addMinutes($step);
            }

            return $slots;
        })->values();
    }

    /**
     * The next `$days` dates with at least one open slot.
     *
     * @return Collection<int, array{date: string, label: string, weekday: string, slots: int}>
     */
    public function availableDates(Doctor $doctor, int $days = self::BOOKING_WINDOW_DAYS): Collection
    {
        $today = CarbonImmutable::today();

        return collect(range(0, $days))
            ->map(fn (int $offset) => $today->addDays($offset))
            ->map(fn (CarbonImmutable $date) => [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('j M'),
                'weekday' => $date->translatedFormat('D'),
                'slots' => $this->slotsFor($doctor, $date)->count(),
            ])
            ->filter(fn (array $day) => $day['slots'] > 0)
            ->values();
    }

    /** Whether a slot is still free — re-checked at submit time to close the race window. */
    public function isSlotAvailable(Doctor $doctor, CarbonImmutable $date, string $time): bool
    {
        return $this->slotsFor($doctor, $date)
            ->contains(fn (array $slot) => $slot['time'] === substr($time, 0, 5));
    }

    public function isDateBookable(CarbonImmutable $date): bool
    {
        $today = CarbonImmutable::today();

        return $date->gte($today) && $date->lte($today->addDays(self::BOOKING_WINDOW_DAYS));
    }

    /** @return array<string, int> keyed by H:i:s */
    private function takenSlotCounts(Doctor $doctor, CarbonImmutable $date): array
    {
        return Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('appointment_time, COUNT(*) as total')
            ->groupBy('appointment_time')
            ->pluck('total', 'appointment_time')
            ->all();
    }

    private function at(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return $date->setTime((int) $h, (int) $m);
    }
}
