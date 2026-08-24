<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SiteFeatures;
use Illuminate\Database\Seeder;

/**
 * Writes a row for every switch on the Site controls page.
 *
 * Not strictly required — SiteFeatures falls back to the registry default when
 * a row is missing — but seeding them means the panel shows real stored state
 * from the first visit, and a key added to the registry later picks up its
 * default here on the next run rather than staying invisible in the database.
 *
 * Idempotent, and deliberately non-destructive: a switch somebody has already
 * turned off keeps its value.
 */
class SiteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SiteFeatures::defaults() as $key => $default) {
            Setting::firstOrCreate(
                ['key' => SiteFeatures::settingKey($key)],
                ['value' => $default ? '1' : '0', 'group' => SiteFeatures::GROUP],
            );
        }

        Setting::flushCache();
    }
}
