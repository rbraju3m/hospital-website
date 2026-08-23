<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiagnosticTest extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /**
     * `code` is deliberately absent — an order code is an identifier the
     * counter reads back, not prose, and stays Latin in both locales.
     *
     * @var list<string>
     */
    protected array $translatable = ['name', 'summary', 'preparation', 'sample_type', 'report_time'];

    protected function casts(): array
    {
        return [
            'is_home_collection' => 'boolean',
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

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Free-text search across name and code, in either script.
     *
     * Patients search for "CBC" as often as for the full name, and a visitor
     * browsing in Bangla still types test names in English — same reasoning as
     * Doctor::search().
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->orWhereTranslatableLike('name', $like)
                ->orWhereTranslatableLike('summary', $like)
                ->orWhere('code', 'like', $like);
        });
    }
}
