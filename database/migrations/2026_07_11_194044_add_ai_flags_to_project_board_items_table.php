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
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false)->after('category_id');
            $table->boolean('is_important')->default(false)->after('is_ai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropColumn(['is_ai', 'is_important']);
        });
    }
};
