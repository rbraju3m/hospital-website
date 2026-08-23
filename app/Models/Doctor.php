<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /**
     * `qualifications` is deliberately absent: degree titles (MBBS, FCPS, MRCP)
     * are formal post-nominals and stay in Latin script in Bangla usage too.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'name', 'designation', 'speciality', 'expertise', 'about', 'languages', 'chamber',
    ];

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

        // Both scripts stay searchable in either locale — see
        // HasTranslations::scopeOrWhereTranslatableLike().
        return $query->where(function (Builder $q) use ($like) {
            foreach (['name', 'speciality', 'designation'] as $column) {
                $q->orWhereTranslatableLike($column, $like);
            }

            $q->orWhere('qualifications', 'like', $like);
        });
    }
}
