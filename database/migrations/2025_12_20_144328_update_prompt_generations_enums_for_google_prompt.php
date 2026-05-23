<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Update generation_type enum to include 'google_huge_prompt'
            // We also include existing ones to be safe
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `generation_type` ENUM('from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze', 'multi_prompt', 'google_huge_prompt') NOT NULL");

            // Change category from ENUM to VARCHAR to support more flexible project types
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'other'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Revert category to enum
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `category` ENUM('design', 'coding', 'writing', 'business', 'education', 'marketing', 'other') NOT NULL DEFAULT 'other'");

            // Revert generation_type enum
            DB::statement("ALTER TABLE `prompt_generations` MODIFY COLUMN `generation_type` ENUM('from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze', 'multi_prompt') NOT NULL");
        }
    }
};
