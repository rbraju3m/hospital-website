<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HealthPackage extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = [
        'name', 'summary', 'description', 'tests', 'duration', 'suitable_for',
    ];

    protected function casts(): array
    {
        return [
            'tests' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function effectivePrice(): int
    {
        return $this->discount_price ?: $this->price;
    }

    public function savingsPercent(): ?int
    {
        if (! $this->discount_price || $this->discount_price >= $this->price) {
            return null;
        }

        return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }
}
