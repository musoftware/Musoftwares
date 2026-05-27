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
        // Safety check
        if (!Schema::hasTable('shop_products')) {
            return;
        }

        // Migrate Shop Products to Affiliate Pos Products
        $oldProducts = \Illuminate\Support\Facades\DB::table('shop_products')->get();
        foreach ($oldProducts as $product) {
            \Illuminate\Support\Facades\DB::table('affiliate_pos_products')->insertOrIgnore([
                'id' => $product->id,
                'user_id' => $product->user_id ?? 1, // Default fallback
                'category_id' => $product->category_id ?? null,
                'name' => $product->name,
                'code' => $product->sku ?? null,
                'price' => $product->price ?? 0,
                'old_price' => $product->compare_price ?? null,
                'short_description' => $product->short_description ?? null,
                'description' => $product->description ?? null,
                'status' => ($product->is_active ?? 1) ? 'active' : 'pending',
                'created_at' => $product->created_at ?? now(),
                'updated_at' => $product->updated_at ?? now(),
            ]);
        }

        // Migrate Shop Orders to Affiliate Pos Orders
        if (Schema::hasTable('shop_orders')) {
            $oldOrders = \Illuminate\Support\Facades\DB::table('shop_orders')->get();
            foreach ($oldOrders as $order) {
                \Illuminate\Support\Facades\DB::table('affiliate_pos_orders')->insertOrIgnore([
                    'id' => $order->id,
                    'tenant_id' => $order->tenant_id ?? null,
                    'customer_id' => $order->user_id ?? null,
                    'total_amount' => $order->total ?? 0,
                    'status' => $order->status ?? 'pending',
                    'payment_status' => $order->payment_status ?? 'unpaid',
                    'created_at' => $order->created_at ?? now(),
                    'updated_at' => $order->updated_at ?? now(),
                ]);
            }
        }

        // Drop the legacy tables
        $tablesToDrop = [
            'shop_product_sku_values',
            'shop_product_skus',
            'shop_option_values',
            'shop_options',
            'shop_order_items',
            'shop_orders',
            'shop_product_images',
            'shop_product_keywords',
            'shop_products',
            'shop_product_categories',
            'shop_governorates',
            'shop_cities',
        ];

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tablesToDrop as $tbl) {
            Schema::dropIfExists($tbl);
        }

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore dropped data
    }
};
