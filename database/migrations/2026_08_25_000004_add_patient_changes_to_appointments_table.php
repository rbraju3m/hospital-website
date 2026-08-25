<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            /* Who cancelled, and when the patient last moved it.
               `status` alone says a booking was cancelled and not by whom, and
               the desk's next action differs: a slot the patient gave up is a
               slot to offer somebody else, while one the desk cancelled is a
               patient somebody may still need to call. */
            $table->string('cancelled_by', 10)->nullable()->after('status');
            $table->timestamp('rescheduled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'rescheduled_at']);
        });
    }
};
