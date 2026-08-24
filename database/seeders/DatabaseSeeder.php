<?php

namespace Database\Seeders;

use Database\Seeders\Translations\DepartmentTranslationSeeder;
use Database\Seeders\Translations\DiagnosticTestTranslationSeeder;
use Database\Seeders\Translations\DoctorTranslationSeeder;
use Database\Seeders\Translations\HealthPackageTranslationSeeder;
use Database\Seeders\Translations\PostTranslationSeeder;
use Database\Seeders\Translations\ServiceTranslationSeeder;
use Database\Seeders\Translations\SettingTranslationSeeder;
use Database\Seeders\Translations\TestimonialTranslationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            SiteFeatureSeeder::class,
            DepartmentSeeder::class,
            DoctorSeeder::class,
            ServiceSeeder::class,
            HealthPackageSeeder::class,
            TestimonialSeeder::class,
            PostSeeder::class,
            DiagnosticTestSeeder::class,
        ]);

        // Bangla content for the records the seeders above create. Kept separate
        // so the English data stays readable and a locale can be added without
        // touching the base seeders.
        $this->call([
            SettingTranslationSeeder::class,
            DepartmentTranslationSeeder::class,
            DoctorTranslationSeeder::class,
            ServiceTranslationSeeder::class,
            HealthPackageTranslationSeeder::class,
            TestimonialTranslationSeeder::class,
            PostTranslationSeeder::class,
            DiagnosticTestTranslationSeeder::class,
        ]);
    }
}
