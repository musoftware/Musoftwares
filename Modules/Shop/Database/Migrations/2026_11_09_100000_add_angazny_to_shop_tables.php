<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->unsignedBigInteger('angazny_id')->nullable()->unique();
            $table->decimal('angazny_price', 10, 2)->nullable();
            $table->decimal('angazny_commission', 10, 2)->nullable();
            $table->string('angazny_depot')->nullable();
            $table->json('angazny_variants')->nullable();
            $table->boolean('angazny_needs_price')->default(false)->index();
        });

        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->string('angazny_status', 20)->nullable()->index();
            $table->string('angazny_order_code', 50)->nullable();
            $table->string('angazny_error', 500)->nullable();
        });

        Schema::table('shop_order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('angazny_stock_id')->nullable();
            $table->decimal('angazny_price_effect', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_order_items', function (Blueprint $table): void {
            $table->dropColumn(['angazny_stock_id', 'angazny_price_effect']);
        });

        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropColumn(['angazny_status', 'angazny_order_code', 'angazny_error']);
        });

        Schema::table('shop_products', function (Blueprint $table): void {
            $table->dropUnique(['angazny_id']);
            $table->dropIndex(['angazny_needs_price']);
            $table->dropColumn(['angazny_id', 'angazny_price', 'angazny_commission', 'angazny_depot', 'angazny_variants', 'angazny_needs_price']);
        });
    }
};
