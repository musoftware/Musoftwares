<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_orders', 'delivery_payload')) {
                $table->json('delivery_payload')->nullable();
            }
            if (!Schema::hasColumn('marketplace_orders', 'auto_complete_at')) {
                $table->timestamp('auto_complete_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_payload', 'auto_complete_at']);
        });
    }
};
