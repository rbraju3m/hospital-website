<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal accounts.
 *
 * A separate table and a separate guard from `users` on purpose: staff and
 * patients must not share an authentication surface. A bug in one must not be
 * able to hand somebody the other, and nothing a patient does can reach /admin.
 *
 * The mobile number is the identity, as the service page has always said —
 * appointments and documents are matched to an account by it, and it is
 * stored in the national ten-digit form so that comparison is exact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 1712345678 — no country code, no trunk zero. PhoneNumber::national().
            $table->string('phone', 16)->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('locale', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Password reset by SMS: email is optional on this table, so a code to
        // the registered mobile is the only recovery route most patients have.
        Schema::create('patient_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16)->index();
            // Hashed — a leaked table must not hand anybody a working code.
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_password_resets');
        Schema::dropIfExists('patients');
    }
};
