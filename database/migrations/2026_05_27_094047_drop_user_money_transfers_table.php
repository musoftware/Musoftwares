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
        Schema::dropIfExists('user_money_transfers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
