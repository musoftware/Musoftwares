<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Add 'multi_prompt' to the generation_type enum
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `generation_type` ENUM('from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze', 'multi_prompt') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Revert back to original enum values
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `generation_type` ENUM('from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze') NOT NULL");
        }
    }
};
