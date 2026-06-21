<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('service_landing_pages');

        Schema::create('service_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('slug')->unique();
            $table->text('hero_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('hero_cta_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('template')->default('modern');
            $table->string('description_alignment')->default('left');
            
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            
            $table->boolean('ab_testing_enabled')->default(false);
            $table->unsignedBigInteger('parent_variant_id')->nullable();
            $table->string('variant_name')->nullable();
            $table->integer('traffic_split_percentage')->default(100);
            $table->boolean('is_winner')->default(false);
            $table->integer('auto_winner_visits')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_landing_pages');
    }
};
