<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns an admin form payload into a translatable model.
 *
 * Admin forms post the fallback locale in ordinary fields and every other
 * locale under translations[<locale>][<column>], which is exactly the shape
 * HasTranslations reads back. List columns (highlights, tests, expertise) are
 * edited as one-item-per-line textareas on both sides and converted here.
 */
trait HandlesTranslatableContent
{
    /**
     * Fill base columns and merge per-locale translations, without saving.
     *
     * A translated field submitted empty is *removed* rather than stored as an
     * empty string — HasTranslations falls back on blank, so a cleared field
     * has to disappear for the fallback to show through again.
     */
    protected function fillTranslatable(Model $model, array $data): Model
    {
        $translations = Arr::pull($data, 'translations', []) ?: [];
        $lists = $this->listColumns($model);

        $model->fill($this->castLists($data, $lists));

        $stored = $model->translations ?? [];

        foreach (translation_locales() as $locale) {
            if (! array_key_exists($locale, $translations)) {
                continue;
            }

            $values = $this->castLists((array) $translations[$locale], $lists);
            $existing = $stored[$locale] ?? [];

            foreach ($values as $column => $value) {
                if (blank($value)) {
                    unset($existing[$column]);
                } else {
                    $existing[$column] = $value;
                }
            }

            $stored[$locale] = $existing;
        }

        // Prune locales left with nothing so the column reads as "untranslated"
        // rather than holding an empty object.
        $stored = array_filter($stored, fn ($values) => filled($values));

        $model->translations = $stored ?: null;

        return $model;
    }

    /**
     * Columns the model casts to array — the ones edited as line-per-item text.
     *
     * Derived from the casts rather than declared per controller so adding a
     * JSON column to a model cannot leave a form silently storing a string.
     *
     * @return list<string>
     */
    protected function listColumns(Model $model): array
    {
        return collect($model->getCasts())
            ->filter(fn ($cast, $column) => $cast === 'array' && $column !== 'translations')
            ->keys()
            ->all();
    }

    /** @param  list<string>  $lists */
    private function castLists(array $data, array $lists): array
    {
        foreach ($lists as $column) {
            if (array_key_exists($column, $data) && ! is_array($data[$column])) {
                $data[$column] = lines_to_array($data[$column]);
            }
        }

        return $data;
    }

    /**
     * Paginate an admin listing, optionally narrowed to rows that are missing a
     * translation for one locale.
     *
     * That filter runs in PHP because missingTranslations() skips fields left
     * blank in the source language, and SQL cannot tell those apart from a
     * genuine gap. The listings are small enough that fetching them costs
     * nothing; ordinary listings still paginate in the database.
     */
    protected function paginateContent(Builder $query, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $locale = $request->query('untranslated');

        if (! is_string($locale) || ! in_array($locale, translation_locales(), true)) {
            return $query->paginate($perPage)->withQueryString();
        }

        $matches = $query->get()->filter(fn (Model $model) => ! $model->isFullyTranslated($locale))->values();
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $matches->forPage($page, $perPage)->values(),
            $matches->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * A URL-safe, unique slug — derived from the title when the field is left
     * blank, and suffixed until it stops colliding.
     */
    protected function uniqueSlug(string $table, ?string $slug, string $fallbackSource, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $fallbackSource) ?: 'item';
        $candidate = $base;
        $suffix = 2;

        while ($this->slugExists($table, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $table, string $slug, ?int $ignoreId): bool
    {
        return DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
