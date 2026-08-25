<?php

namespace App\Models\Concerns;

use App\Support\TranslationGaps;
use Illuminate\Database\Eloquent\Builder;

/**
 * Locale-aware attribute reads for editorial content.
 *
 * The fallback locale lives in the ordinary columns; other locales live in a
 * `translations` JSON column keyed by locale then column name. Reads are
 * transparent — `$doctor->name` returns Bangla under a Bangla request and the
 * original column otherwise — so views and queries need no locale awareness.
 *
 * Models opt in with:  protected array $translatable = ['name', 'summary'];
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        $this->mergeCasts(['translations' => 'array']);
    }

    /**
     * The panel's menu carries a count of content still waiting to be
     * translated. It is counted in PHP over every row and cached forever, so
     * the cache has to go whenever any translatable row changes — here rather
     * than in the controllers, because a seeder, a console command and an
     * import are all just as able to leave that number wrong. Only this
     * model's own section is dropped; a model with no section is a no-op.
     */
    public static function bootHasTranslations(): void
    {
        static::saved(fn () => TranslationGaps::flushFor(static::class));
        static::deleted(fn () => TranslationGaps::flushFor(static::class));
    }

    /**
     * Intercepts attribute reads only — relations resolve through getAttribute()
     * and never reach here, so eager loading and `with()` are unaffected.
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (! in_array($key, $this->translatable ?? [], true)) {
            return $value;
        }

        $translated = $this->translation($key);

        // Fall through on null *and* on empty string: a half-filled translation
        // should show the original rather than a blank field.
        return blank($translated) ? $value : $translated;
    }

    /** Which attributes this model stores per locale. @return list<string> */
    public function translatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /** The raw stored value, ignoring the active locale. */
    public function untranslated(string $key): mixed
    {
        return parent::getAttributeValue($key);
    }

    /** The translation for one attribute, or null when there is none. */
    public function translation(string $key, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if ($locale === config('app.fallback_locale')) {
            return null;
        }

        return data_get($this->attributes['translations'] ?? null
            ? json_decode($this->attributes['translations'], true)
            : null, "{$locale}.{$key}");
    }

    /**
     * Whether every translatable attribute that HAS a source value also has a
     * translation. Fields left empty in the fallback locale are skipped —
     * there is nothing to translate, so their absence is not a gap.
     */
    public function isFullyTranslated(?string $locale = null): bool
    {
        return $this->missingTranslations($locale) === [];
    }

    /**
     * Translatable attributes that have a source value but no translation.
     *
     * @return list<string>
     */
    public function missingTranslations(?string $locale = null): array
    {
        $missing = [];

        foreach ($this->translatable ?? [] as $key) {
            if (blank($this->untranslated($key))) {
                continue;
            }

            if (blank($this->translation($key, $locale))) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /** Merge translations for one locale without disturbing the others. */
    public function setTranslations(string $locale, array $values): static
    {
        $all = $this->translations ?? [];
        $all[$locale] = array_merge($all[$locale] ?? [], $values);
        $this->translations = $all;

        return $this;
    }

    /**
     * A SQL expression returning the translated column for the active locale,
     * falling back to the base column. Use it when the database — not PHP —
     * has to do the work, i.e. searching and sorting.
     */
    public function translatedColumn(string $column, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === config('app.fallback_locale')) {
            return $column;
        }

        // Column and locale are developer-supplied, never request input.
        return sprintf(
            "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(translations, '$.\"%s\".\"%s\"')), ''), %s)",
            $locale,
            $column,
            $column
        );
    }

    /** A SQL expression returning just the stored translation, with no fallback. */
    public function translationExpression(string $column, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        // Column and locale are developer-supplied, never request input.
        return sprintf(
            "JSON_UNQUOTE(JSON_EXTRACT(translations, '$.\"%s\".\"%s\"'))",
            $locale,
            $column
        );
    }

    /**
     * Match a translatable column in the base language OR the active locale.
     *
     * Deliberately not COALESCE: a visitor browsing in Bangla may still type a
     * consultant's name in English, and vice versa, so both have to be
     * searchable regardless of which locale is active.
     */
    public function scopeOrWhereTranslatableLike(Builder $query, string $column, string $like): Builder
    {
        $query->orWhere($column, 'like', $like);

        if (app()->getLocale() !== config('app.fallback_locale')) {
            $query->orWhereRaw($this->translationExpression($column).' LIKE ?', [$like]);
        }

        return $query;
    }
}
