<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_board_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // cards | grid | lines | table
            $table->string('view_mode', 16)->default('cards');
            // manual | title | type | lane | priority | category
            $table->string('sort_by', 16)->default('manual');
            $table->string('sort_dir', 4)->default('asc');

            $table->timestamps();

            $table->unique(['user_id', 'project_id'], 'pbp_user_project_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_board_preferences');
    }
};
