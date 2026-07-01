<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_board_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // The day board this sticky note belongs to.
            $table->date('for_date');

            $table->text('content')->nullable();
            $table->string('color')->default('yellow');

            $table->timestamps();

            $table->index(['project_id', 'for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_board_notes');
    }
};
