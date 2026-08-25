<?php

namespace App\Support;

use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\Doctor;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Slide;
use App\Models\Testimonial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * How much content is still waiting to be translated, per section of the panel.
 *
 * The tests prove the locale *files* are complete; nothing proves the same of
 * the content, and a missing translation does not break a page — it falls back
 * — so the only way anybody finds one is by being told. The listings already
 * chip a row that is short; this is the same fact one level up, on the menu.
 *
 * **This cannot be a query.** `missingTranslations()` skips a field that is
 * blank in the source too, because there is nothing to translate there, and a
 * `JSON_EXTRACT` cannot tell that apart from a real gap — the same reason the
 * `?untranslated=` filter runs in PHP. So it is counted in PHP over every row
 * of eight tables, which is far too much work for a page load, and cached
 * forever instead. `HasTranslations` drops the cache whenever anything
 * translatable is saved or deleted, so the count is exact rather than
 * fresh-ish: staff look at this number immediately after editing the thing it
 * counts, and one that lags by a minute would be read as a bug in the editing.
 *
 * **Cached per section, not as one number.** Saving a doctor invalidates the
 * doctors count; everything else stands. One key for all eight would mean
 * every save in the panel charging the next page load with a full recount, and
 * the recount cannot be handed to the queue — this box has no worker running,
 * so the badge would simply never come back.
 */
class TranslationGaps
{
    private const CACHE_PREFIX = 'translation.gaps.';

    /**
     * Menu key => the models behind that section. The keys are
     * PanelNavigation's, so a badge cannot end up on a section that does not
     * exist; PanelNavigationTest asserts the two lists agree.
     */
    private const SOURCES = [
        'slides' => Slide::class,
        'departments' => Department::class,
        'doctors' => Doctor::class,
        'services' => Service::class,
        'packages' => HealthPackage::class,
        'diagnostics' => DiagnosticTest::class,
        'posts' => Post::class,
        // An album and its photographs are one section of the panel: a caption
        // nobody translated is as much of a gap as an album title.
        'gallery' => [GalleryAlbum::class, GalleryPhoto::class],
        'testimonials' => Testimonial::class,
    ];

    /** The menu keys this counts for. @return list<string> */
    public static function sections(): array
    {
        return array_keys(self::SOURCES);
    }

    /** @return array<string, int> keyed by menu key, sections with no gap omitted */
    public static function counts(): array
    {
        // A single-locale installation has nothing to be short of.
        if (translation_locales() === []) {
            return [];
        }

        /* One read for all eight sections rather than eight — the cache store
           here is the database, so each `get()` is its own round trip and the
           menu renders on every page in the panel. */
        $cached = Cache::many(array_map(
            fn (string $key) => self::CACHE_PREFIX.$key,
            array_keys(self::SOURCES),
        ));

        $counts = [];

        foreach (array_keys(self::SOURCES) as $key) {
            $count = $cached[self::CACHE_PREFIX.$key];

            if ($count === null) {
                $count = self::tally($key);
                Cache::forever(self::CACHE_PREFIX.$key, $count);
            }

            if ($count > 0) {
                $counts[$key] = $count;
            }
        }

        return $counts;
    }

    /** Drop the count for whichever section this model belongs to, if any. */
    public static function flushFor(string $model): void
    {
        foreach (self::SOURCES as $key => $models) {
            if (in_array($model, Arr::wrap($models), true)) {
                Cache::forget(self::CACHE_PREFIX.$key);
            }
        }
    }

    public static function flush(): void
    {
        foreach (array_keys(self::SOURCES) as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    private static function tally(string $key): int
    {
        $locales = translation_locales();
        $count = 0;

        foreach (Arr::wrap(self::SOURCES[$key]) as $model) {
            /* Whole rows, and lazily. A partial select drops `translations`
               and every row would count as translated; `all()` would hold a
               whole table in memory for a number in a sidebar. */
            $count += $model::query()->lazy()->filter(
                fn ($record) => collect($locales)->contains(
                    fn (string $locale) => $record->missingTranslations($locale) !== []
                )
            )->count();
        }

        return $count;
    }
}
