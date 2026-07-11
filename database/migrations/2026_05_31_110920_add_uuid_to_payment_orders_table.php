<?php

use App\Models\PaymentOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('payment_orders', 'uuid')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            foreach (PaymentOrder::all() as $order) {
                $order->uuid = (string) Str::uuid();
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
