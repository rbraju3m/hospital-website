<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // executive | cardiac | diabetes | women | men | senior | basic
            $table->string('category')->default('basic');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('discount_price')->nullable();
            $table->json('tests')->nullable();
            $table->string('duration')->nullable();
            $table->string('suitable_for')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_packages');
    }
};
