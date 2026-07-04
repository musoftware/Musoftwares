<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            // Card-level classification (Urgent / Important / Normal / Idea / Custom).
            // Nullable so cards created before categories existed keep working.
            // set null on delete: a deleted category leaves the card un-categorized rather than deleted.
            $table->foreignId('category_id')
                ->nullable()
                ->after('sort')
                ->constrained('project_board_categories')
                ->nullOnDelete();
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
