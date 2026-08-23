<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    /**
     * All settings as a key => value map.
     *
     * Deliberately not named all()/get() — those are forwarded to the query
     * builder by Model::__callStatic and overriding them breaks Eloquent.
     */
    public static function cachedMap(): array
    {
        return Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return static::cachedMap()[$key] ?? $default;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }
}
