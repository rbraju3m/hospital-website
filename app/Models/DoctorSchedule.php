<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }

    public function timeRange(): string
    {
        return $this->formatTime($this->start_time).' – '.$this->formatTime($this->end_time);
    }

    private function formatTime(string $time): string
    {
        return date('g:i A', strtotime($time));
    }
}
