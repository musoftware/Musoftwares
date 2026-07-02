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
        if (!Schema::hasColumn('payment_orders', 'uuid')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            foreach (\App\Models\PaymentOrder::all() as $order) {
                $order->uuid = (string) \Illuminate\Support\Str::uuid();
                $order->save();
            }

            Schema::table('payment_orders', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
