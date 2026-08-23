<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('designation')->nullable();
            $table->string('qualifications')->nullable();
            $table->string('speciality')->nullable();
            $table->json('expertise')->nullable();
            $table->string('photo')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->longText('about')->nullable();
            $table->json('languages')->nullable();
            $table->string('chamber')->nullable();
            $table->unsignedInteger('consultation_fee')->default(0);
            $table->unsignedInteger('follow_up_fee')->nullable();
            $table->boolean('accepts_online_appointment')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'department_id']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
