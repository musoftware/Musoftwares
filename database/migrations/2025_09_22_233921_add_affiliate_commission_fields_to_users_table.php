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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('affiliate_commission_percentage', 5, 2)->default(1.00)->comment('Commission percentage for affiliate (1.00 = 1%)');
            $table->boolean('add_commission_to_total')->default(false)->comment('Whether to add commission to invoice total instead of deducting from it');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['affiliate_commission_percentage', 'add_commission_to_total']);
        });
    }
};
