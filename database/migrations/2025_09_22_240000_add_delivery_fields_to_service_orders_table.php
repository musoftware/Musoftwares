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
        // Add delivery fields
        Schema::table('service_orders', function (Blueprint $table) {
            $table->text('delivery_message')->nullable()->after('status');
            $table->string('serial_key')->nullable()->after('delivery_message');
            $table->timestamp('delivered_at')->nullable()->after('serial_key');
            $table->timestamp('started_at')->nullable()->after('delivered_at');
        });

        // Update status enum - skip on SQLite (no enum support needed)
        if (config('database.default') !== 'sqlite') {
            Schema::table('service_orders', function (Blueprint $table) {
                $table->enum('status', ['pending', 'active', 'late', 'delivered', 'completed', 'cancelled'])->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_message', 'serial_key', 'delivered_at', 'started_at']);
            $table->enum('status', ['active', 'late', 'delivered', 'completed', 'cancelled'])->default('active')->change();
        });
    }
};
