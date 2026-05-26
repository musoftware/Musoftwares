<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_pos_cart_items', function (Blueprint $table) {
            $table->string('website_commission_type')->nullable()->after('commission');
            $table->double('website_commission', 10, 2)->default(0)->after('website_commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_pos_cart_items', function (Blueprint $table) {
            $table->dropColumn(['website_commission_type', 'website_commission']);
        });
    }
};
