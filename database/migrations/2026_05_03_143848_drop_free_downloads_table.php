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
        Schema::dropIfExists('free_downloads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not restoring since this is fully deprecated in favor of standard Services
    }
};
