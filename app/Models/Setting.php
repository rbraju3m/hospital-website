<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = ['value'];

    /**
     * Settings whose value is a label rather than data. Phone numbers, emails
     * and URLs are identical in every locale and are never translated.
     */
    public const TRANSLATABLE_KEYS = [
        'site_name',
        'site_tagline',
        'accreditation',
        'address_line',
        'address_city',
        'opening_hours',
        'stat_patients_yearly',
    ];

    /**
     * key => value for one locale, cached per locale.
     *
     * Deliberately not named all()/get() — those are forwarded to the query
     * builder by Model::__callStatic and overriding them breaks Eloquent.
     */
    public static function cachedMap(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return Cache::rememberForever(
            static::cacheKey($locale),
            fn () => static::query()->get()->mapWithKeys(fn (self $setting) => [
                $setting->key => $setting->value,
            ])->all()
        );
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return static::cachedMap()[$key] ?? $default;
    }

    private static function cacheKey(string $locale): string
    {
        return "settings.all.{$locale}";
    }

    /** Every locale's map has to go — a saved setting may change any of them. */
    public static function flushCache(): void
    {
        foreach (array_keys(config('app.available_locales', ['en' => []])) as $locale) {
            Cache::forget(static::cacheKey($locale));
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
