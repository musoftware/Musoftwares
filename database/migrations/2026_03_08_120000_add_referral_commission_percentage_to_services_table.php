<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Optional per-service referral commission percentage. When set, overrides platform default (10% first month, then 1%).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('referral_commission_percentage', 5, 2)->nullable()->after('referral_commission_from')
                ->comment('Custom affiliate % for this service; null = platform default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('referral_commission_percentage');
        });
    }
};
