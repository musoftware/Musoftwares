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
        Schema::table('todos', function (Blueprint $table) {
            $table->boolean('refunded')->default(false)->after('is_paid');
            $table->timestamp('refunded_at')->nullable()->after('refunded');
            $table->decimal('refund_amount', 15, 2)->default(0.00)->after('refunded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn(['refunded', 'refunded_at', 'refund_amount']);
        });
    }
};
