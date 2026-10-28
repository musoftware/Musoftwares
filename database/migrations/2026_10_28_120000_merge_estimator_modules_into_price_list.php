<?php

use App\Services\ProjectEstimatorDataService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One price list: the estimator's add-ons move into contract_price_items, which contracts and the
 * AI pricing already use. An item shown in the estimator has a platform; its price is price_usd,
 * or its hours times the hourly rate when it has no fixed price. Items already in the table are
 * never changed, so prices the admin edited stay as they are.
 */
return new class extends Migration
{
    private const HOURLY_RATE_USD = 25;

    public function up(): void
    {
        Schema::table('contract_price_items', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_price_items', 'platform')) {
                $table->string('platform', 20)->nullable()->after('key');
            }
            if (! Schema::hasColumn('contract_price_items', 'category')) {
                $table->string('category', 40)->nullable()->after('platform');
            }
            if (! Schema::hasColumn('contract_price_items', 'price_usd')) {
                $table->decimal('price_usd', 10, 2)->nullable()->after('default_price');
            }
            if (! Schema::hasColumn('contract_price_items', 'icon')) {
                $table->string('icon', 40)->nullable();
            }
            if (! Schema::hasColumn('contract_price_items', 'meta')) {
                $table->json('meta')->nullable();
            }
        });

        $usd = DB::table('currencies')->where('currency', 'USD')->value('id');
        $order = (int) DB::table('contract_price_items')->max('sort_order');
        $now = now();

        foreach ((new ProjectEstimatorDataService)->getEstimatorData(1, false)['modules'] as $platform => $modules) {
            foreach ($modules as $module) {
                if (DB::table('contract_price_items')->where('key', $module['id'])->exists()) {
                    continue;
                }

                $price = (float) $module['price_usd'];
                $hours = max(1, (int) round($price / self::HOURLY_RATE_USD));
                $meta = array_intersect_key($module, ['isCounter' => true, 'max' => true]);

                DB::table('contract_price_items')->insert([
                    'key' => $module['id'],
                    'name' => $module['title'],
                    'name_en' => $module['title'],
                    'name_ar' => $module['title_ar'] ?? $module['title'],
                    'description' => $module['desc'] ?? null,
                    'platform' => $platform,
                    'category' => $module['category'] ?? null,
                    'price_usd' => $price,
                    'default_price' => $price,
                    'currency_id' => $usd,
                    'icon' => $module['icon'] ?? null,
                    'meta' => $meta ? json_encode($meta) : null,
                    'standalone_hours' => $hours,
                    'marginal_hours' => max(1, (int) round($hours * 0.6)),
                    'complexity' => 'medium',
                    'is_active' => true,
                    'sort_order' => ++$order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('contract_price_items')->whereNotNull('platform')->whereNotNull('price_usd')->delete();
        Schema::table('contract_price_items', function (Blueprint $table) {
            $table->dropColumn(['platform', 'category', 'price_usd', 'icon', 'meta']);
        });
    }
};
