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
        Schema::dropIfExists('freelancer_task_comments');
        Schema::dropIfExists('freelancer_earnings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
