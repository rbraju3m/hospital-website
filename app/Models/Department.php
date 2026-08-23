<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = [
        'name', 'tagline', 'summary', 'description', 'highlights',
        'treatments', 'location', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'treatments' => 'array',
            'is_centre_of_excellence' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCentresOfExcellence(Builder $query): Builder
    {
        return $query->where('is_centre_of_excellence', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
