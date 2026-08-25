<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One panel of the home page's slider.
 *
 * Deliberately not a general-purpose "banner anywhere" type: it is the top of
 * the home page and nothing else, which is what lets the fields be this
 * specific — an eyebrow, a headline, a sentence, and at most two buttons.
 *
 * The image is optional and falls back to stand-in photography like every
 * other picture on the site, so the seeded slides are a working slider before
 * anybody has uploaded anything. A slider with no *slides* is a different
 * matter: the layout falls back to the classic hero rather than rendering an
 * empty band, because the top of the home page cannot be nothing.
 */
class Slide extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** URLs are not translated: one address serves both locales. */
    protected array $translatable = [
        'eyebrow', 'title', 'subtitle', 'cta_label', 'cta_secondary_label',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The picture, or a stand-in, or nothing.
     *
     * `image_url()` answers all three — with `behaviour_demo_images` off and no
     * upload it returns null, and the slide renders as a plain navy panel with
     * its words on it rather than a broken frame.
     */
    public function url(): ?string
    {
        return image_url($this->image, 'hero', $this->id, 'slides');
    }
}
