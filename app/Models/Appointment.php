<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'reminded_at' => 'datetime',
            'rescheduled_at' => 'datetime',
        ];
    }

    /** The appointment as one moment, for comparing against now. */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->appointment_date->toDateString().' '.$this->appointment_time
        );
    }

    /**
     * May the patient still move or cancel this from the portal?
     *
     * Three things have to be true: the booking is one the desk has not
     * already closed, the visit has not happened, and staff have left the
     * portal's change controls switched on. A booking at `completed` is
     * bookkeeping about a visit that already took place, and one already
     * `cancelled` is not a booking.
     */
    public function isChangeableByPatient(): bool
    {
        return feature('behaviour_portal_changes')
            && in_array($this->status, ['pending', 'confirmed'], true)
            && $this->startsAt()->isFuture();
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function formattedTime(): string
    {
        return date('g:i A', strtotime($this->appointment_time));
    }
}
