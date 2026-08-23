<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The published diagnostics price list.
 *
 * Deliberately not related to health_packages.tests, which stays a free-text
 * JSON list: the package copy reads as prose ("Complete blood count & ESR")
 * and mapping it onto catalogue rows is a judgement call per package, not a
 * migration. The two can be joined once the catalogue's names settle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_tests', function (Blueprint $table) {
            $table->id();
            $table->json('translations')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            // The hospital's own order code, quoted at the counter. Not
            // translated — it is an identifier, like a doctor's post-nominals.
            $table->string('code', 32)->nullable();
            // pathology | imaging | cardiology | endoscopy
            $table->string('category')->default('pathology');
            $table->text('summary')->nullable();
            // "Fast for 12 hours", "Bring previous films" — the thing patients
            // most often get wrong and have to come back for.
            $table->text('preparation')->nullable();
            $table->string('sample_type')->nullable();
            $table->string('report_time')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('discount_price')->nullable();
            $table->boolean('is_home_collection')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_tests');
    }
};
