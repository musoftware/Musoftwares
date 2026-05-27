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
        Schema::dropIfExists('prompt_correct_history_inlines');
        Schema::dropIfExists('prompt_correct_histories');
        Schema::dropIfExists('prompt_generation_sessions');
        Schema::dropIfExists('prompt_generations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
