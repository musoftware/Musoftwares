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
        Schema::dropIfExists('community_members');
        Schema::dropIfExists('communities');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
