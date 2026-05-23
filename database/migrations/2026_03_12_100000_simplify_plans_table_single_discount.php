<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove hour rate, new feature, support fields from plans.
     * Add single discount_percentage field.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'hour_rate_price',
                'new_feature_status',
                'discount_new_feature_percentage',
                'support_status',
                'discount_support_percentage',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->double('discount_percentage', 8, 2)->nullable()->after('plan_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->double('hour_rate_price', 8, 2)->nullable()->after('plan_currency');
            $table->boolean('new_feature_status')->default(0)->after('hour_rate_price');
            $table->double('discount_new_feature_percentage', 8, 2)->nullable()->after('new_feature_status');
            $table->boolean('support_status')->default(0)->after('discount_new_feature_percentage');
            $table->boolean('discount_support_percentage')->default(0)->after('support_status');
        });
    }
};
