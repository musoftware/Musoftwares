<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_board_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // Stable identifier used in seed URLs and react keys.
            $table->string('slug', 50);
            // Display labels per locale. Empty string falls back to slug.
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            // Tailwind class fragments; consumers may also use this as a Hex if they prefer.
            $table->string('color', 30)->default('slate');
            $table->string('text_color', 30)->default('slate');
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug'], 'pbc_project_slug_unique');
            $table->index(['project_id', 'sort'], 'pbc_project_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_board_categories');
    }
};
