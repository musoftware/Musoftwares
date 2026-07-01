<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic placement of any card (ProjectBoardNote, Task, ProjectReport) on a per-day board.
        Schema::create('project_board_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->date('for_date');

            $table->string('itemable_type');
            $table->unsignedBigInteger('itemable_id');

            $table->string('lane')->default('backlog');
            $table->integer('pos_x')->nullable();
            $table->integer('pos_y')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'for_date', 'itemable_type', 'itemable_id'], 'pbi_card_unique');
            $table->index(['project_id', 'for_date']);
            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_board_items');
    }
};
