<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Safety check if old tables exist
        if (!Schema::hasTable('services')) {
            return;
        }

        // 1. Migrate Categories
        if (Schema::hasTable('service_categories')) {
            $categories = DB::table('service_categories')->get();
            foreach ($categories as $category) {
                DB::table('marketplace_service_categories')->insertOrIgnore([
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->icon,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ]);
            }
        }

        // 2. Migrate Services
        $services = DB::table('services')->get();
        foreach ($services as $service) {
            $statusMap = [
                'active' => 'active',
                'approved' => 'active',
                'pending' => 'draft',
                'reviewing' => 'draft',
                'declined' => 'banned',
                'rejected' => 'banned',
                'suspended' => 'banned',
                'paused' => 'paused',
            ];
            $newStatus = $statusMap[$service->status] ?? 'draft';

            if (!$service->user_id) continue;

            DB::table('marketplace_services')->insertOrIgnore([
                'id' => $service->id,
                'seller_id' => $service->user_id,
                'category_id' => $service->service_category_id,
                'title' => $service->title,
                'description' => $service->description ?? '',
                'status' => $newStatus,
                'is_featured' => $service->featured ?? 0,
                'deleted_at' => $service->deleted_at,
                'created_at' => $service->created_at,
                'updated_at' => $service->updated_at,
            ]);
        }

        // 3. Migrate Packages
        if (Schema::hasTable('service_packages')) {
            $packages = DB::table('service_packages')->get();
            foreach ($packages as $pkg) {
                if (!DB::table('marketplace_services')->where('id', $pkg->service_id)->exists()) continue;

                DB::table('marketplace_packages')->insertOrIgnore([
                    'id' => $pkg->id,
                    'service_id' => $pkg->service_id,
                    'name' => $pkg->name ?? $pkg->title ?? 'Standard',
                    'description' => $pkg->description ?? '',
                    'price' => $pkg->price,
                    'currency_code' => 'USD',
                    'delivery_days' => $pkg->delivery_days ?? 1,
                    'created_at' => $pkg->created_at,
                    'updated_at' => $pkg->updated_at,
                ]);
            }
        }

        // 4. Migrate Orders
        if (Schema::hasTable('service_orders')) {
            $orders = DB::table('service_orders')->get();
            foreach ($orders as $order) {
                $service = DB::table('services')->where('id', $order->service_id)->first();
                if (!$service || !$service->user_id) continue;
                if (!$order->user_id) continue;

                $pkg = DB::table('marketplace_packages')->where('service_id', $service->id)->first();
                $pkgId = $pkg ? $pkg->id : null;
                
                if (!$pkgId) continue;

                $commission = max(0, $order->buyer_service_amount - $order->seller_service_amount);
                
                DB::table('marketplace_orders')->insertOrIgnore([
                    'id' => $order->id,
                    'buyer_id' => $order->user_id,
                    'seller_id' => $service->user_id,
                    'package_id' => $pkgId,
                    'amount' => $order->buyer_service_amount ?? 0,
                    'currency_code' => 'USD',
                    'commission_amount' => $commission,
                    'status' => $order->status,
                    'delivered_at' => $order->delivered_at,
                    'completed_at' => $order->completed_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }

        // Drop the old tables
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('services');
        
        $tablesToDrop = [
            'service_discounts', 'service_extras', 'service_faqs', 'service_files', 
            'service_histories', 'service_images', 'service_landing_faqs', 
            'service_landing_form_submissions', 'service_landing_page_ab_metrics', 
            'service_landing_page_cta_variants', 'service_landing_pages', 
            'service_landing_pricing_tables', 'service_landing_questions', 
            'service_order_extras', 'service_serials', 'service_share_unlocks', 
            'service_translations'
        ];
        
        foreach ($tablesToDrop as $tbl) {
            Schema::dropIfExists($tbl);
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function down()
    {
    }
};


