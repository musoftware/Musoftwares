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
            // Change generated_prompt and analysis_notes to LONGTEXT to support large multi-prompt outputs
            DB::statement('ALTER TABLE prompt_generations MODIFY generated_prompt LONGTEXT');
            DB::statement('ALTER TABLE prompt_generations MODIFY analysis_notes LONGTEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            // Revert back to TEXT
            DB::statement('ALTER TABLE prompt_generations MODIFY generated_prompt TEXT');
            DB::statement('ALTER TABLE prompt_generations MODIFY analysis_notes TEXT');
        }
    }
};
