<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'languages' => 'array',
            'accepts_online_appointment' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Initials used by the avatar placeholder when no photo is uploaded. */
    public function initials(): string
    {
        $name = trim(preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?)\s+/i', '', $this->name));
        $parts = preg_split('/\s+/', $name) ?: [];

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr(end($parts) ?: '', 0, 1));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Free-text search across name, speciality and designation. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('speciality', 'like', $like)
                ->orWhere('designation', 'like', $like)
                ->orWhere('qualifications', 'like', $like);
        });
    }
}
