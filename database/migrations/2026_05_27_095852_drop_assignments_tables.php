<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('assignment_ocr_texts');
        Schema::dropIfExists('assignment_screenshots');
        Schema::dropIfExists('assignment_sessions');
        Schema::dropIfExists('assignments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
