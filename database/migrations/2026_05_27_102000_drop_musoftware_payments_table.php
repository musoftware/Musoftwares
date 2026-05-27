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
        Schema::dropIfExists('musoftware_payments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed as this table is permanently replaced by gateway_payments
    }
};
