<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports, prescriptions and bills published to a patient.
 *
 * Keyed by mobile number rather than by patient_id: a lab report exists before
 * the patient gets round to registering, and it should be waiting for them
 * when they do rather than needing someone to re-attach it.
 *
 * Files live on the private disk and are streamed by a controller that checks
 * ownership. They must never be reachable through the public storage symlink —
 * these are medical records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            // National ten-digit form, matching patients.phone.
            $table->string('phone', 16)->index();
            $table->string('title');
            // report | prescription | bill
            $table->string('category')->default('report');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 128);
            $table->unsignedInteger('size');
            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
