<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The language the patient booked in.
 *
 * The confirmation email can be sent in the request locale, but a status
 * change is triggered by a staff member days later — quite possibly one
 * working in the other language. Without this, a patient who booked in Bangla
 * would be told in English that their appointment is confirmed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('locale', 8)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
