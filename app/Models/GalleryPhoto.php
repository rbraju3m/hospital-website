<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One picture in an album.
 *
 * Deliberately thin: a caption, an order and a file. There is no `is_active`
 * here as there is on every other content model — a photograph has no URL of
 * its own to leave dangling, so hiding one and deleting one are the same act,
 * and a second switch would only be somewhere for a picture to get lost.
 */
class GalleryPhoto extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = ['caption'];

    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Recent photographs from albums the site is showing, ready to render.
     *
     * Shared by the home band and the About strip so the two cannot drift.
     * Rows with nothing to show — no upload, stand-in imagery switched off —
     * are dropped here rather than rendered as empty frames, which is why it
     * reads more rows than it returns.
     */
    public static function recent(int $limit = 8): Collection
    {
        return static::query()
            ->whereHas('album', fn ($query) => $query->active())
            ->with('album')
            ->orderByDesc('id')
            ->take($limit * 2)
            ->get()
            ->filter(fn (self $photo) => filled($photo->url()))
            ->take($limit)
            ->values();
    }

    /** What the site renders for this slot: the upload, or a stand-in. */
    public function url(): ?string
    {
        return image_url($this->untranslated('path'), 'cover', $this->id, 'gallery');
    }
}
