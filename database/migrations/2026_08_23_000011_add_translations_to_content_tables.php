<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a single JSON `translations` column to every table holding editorial
 * content, shaped as { "<locale>": { "<column>": "<translated value>" } }.
 *
 * One column per table rather than one column per translatable field: adding a
 * new translatable field then costs nothing, and the fallback locale keeps
 * living in the ordinary columns so every existing query still works.
 */
return new class extends Migration
{
    private const TABLES = [
        'departments',
        'doctors',
        'services',
        'health_packages',
        'testimonials',
        'posts',
        'settings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('translations')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('translations');
            });
        }
    }
};
