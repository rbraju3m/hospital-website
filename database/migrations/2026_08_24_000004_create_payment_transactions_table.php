<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_document_id')->constrained()->cascadeOnDelete();
            $table->string('tran_id', 64)->unique();
            $table->unsignedInteger('amount');
            $table->string('status')->default('initiated');
            $table->string('val_id')->nullable();
            $table->string('card_type')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['patient_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
