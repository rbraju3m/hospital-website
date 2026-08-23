<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the day-before reminder went out.
 *
 * The whole point is idempotence: cron can double-fire, a run can be repeated
 * by hand after a failure, and a patient must not be reminded twice. This
 * column is what the command filters on, so a second run is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('locale');
            $table->index(['appointment_date', 'reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['appointment_date', 'reminded_at']);
            $table->dropColumn('reminded_at');
        });
    }
};
