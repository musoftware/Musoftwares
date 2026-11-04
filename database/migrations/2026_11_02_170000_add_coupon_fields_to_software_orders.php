<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('software_orders')) {
            Schema::table('software_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('software_orders', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->after('amount')->constrained('coupons')->nullOnDelete();
                }
                if (! Schema::hasColumn('software_orders', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0)->after('coupon_id');
                }
                if (! Schema::hasColumn('software_orders', 'coupon_code')) {
                    $table->string('coupon_code', 50)->nullable()->after('discount_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('software_orders')) {
            Schema::table('software_orders', function (Blueprint $table) {
                if (Schema::hasColumn('software_orders', 'coupon_id')) {
                    if (DB::getDriverName() !== 'sqlite') {
                        $table->dropForeign(['coupon_id']);
                    }
                    $table->dropColumn('coupon_id');
                }
                if (Schema::hasColumn('software_orders', 'discount_amount')) {
                    $table->dropColumn('discount_amount');
                }
                if (Schema::hasColumn('software_orders', 'coupon_code')) {
                    $table->dropColumn('coupon_code');
                }
            });
        }
    }
};

