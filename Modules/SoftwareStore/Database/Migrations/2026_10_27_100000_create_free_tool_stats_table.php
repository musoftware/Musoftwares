<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public view counter of each free tool (every page view counts, not unique visitors).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_tool_stats', function (Blueprint $table) {
            $table->string('slug', 64)->primary();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_tool_stats');
    }
};
