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
        if (config('database.default') !== 'sqlite') {
            // Using raw statement to avoid doctrine/dbal dependency issues with enums
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE prompt_generations MODIFY COLUMN generation_type VARCHAR(100)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Revert to original enum list if needed (approximate)
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE prompt_generations MODIFY COLUMN generation_type ENUM('from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze')");
        }
    }
};
