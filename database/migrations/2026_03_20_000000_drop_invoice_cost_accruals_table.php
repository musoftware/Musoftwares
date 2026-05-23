<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('invoice_cost_accruals');
    }

    public function down(): void
    {
        // Intentionally empty: accruals table removed from app design.
    }
};
