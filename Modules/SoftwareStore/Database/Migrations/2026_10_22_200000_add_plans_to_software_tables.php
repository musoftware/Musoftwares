<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('software_products', 'plans')) {
            Schema::table('software_products', function (Blueprint $table) {
                $table->json('plans')->nullable()->after('currency');
            });
        }

        if (! Schema::hasColumn('software_orders', 'plan_key')) {
            Schema::table('software_orders', function (Blueprint $table) {
                $table->string('plan_key', 50)->nullable()->after('software_product_id');
                $table->string('plan_name', 120)->nullable()->after('plan_key');
            });
        }
    }

    public function down(): void
    {
        Schema::table('software_orders', function (Blueprint $table) {
            $table->dropColumn(['plan_key', 'plan_name']);
        });
        Schema::table('software_products', function (Blueprint $table) {
            $table->dropColumn('plans');
        });
    }
};
