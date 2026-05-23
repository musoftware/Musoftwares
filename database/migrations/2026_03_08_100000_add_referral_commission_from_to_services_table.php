<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Service owner choice: referral commission deducted from platform fee or from seller's price.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('referral_commission_from', 32)->default('fee')->after('allow_random_serial')
                ->comment('fee = deduct from platform fee; seller_price = deduct from seller price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('referral_commission_from');
        });
    }
};
