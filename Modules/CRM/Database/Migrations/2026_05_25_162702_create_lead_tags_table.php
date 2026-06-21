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
        Schema::create('lead_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // The tenant who owns the tag
            $table->string('name');
            $table->string('color')->default('#3b82f6'); // Default to blue
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'name']);
        });

        Schema::create('lead_tag', function (Blueprint $table) {
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('lead_tags')->cascadeOnDelete();
            $table->primary(['lead_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('lead_tags');
    }
};
