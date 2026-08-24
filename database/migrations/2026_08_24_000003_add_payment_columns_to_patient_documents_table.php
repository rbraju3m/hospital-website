<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->unsignedInteger('amount')->nullable()->after('category');
            $table->string('payment_status')->default('unpaid')->after('amount');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->index(['category', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->dropIndex(['category', 'payment_status']);
            $table->dropColumn(['amount', 'payment_status', 'paid_at']);
        });
    }
};
