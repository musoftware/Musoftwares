<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('musoftware_payments', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(40.00)->after('status');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_rate');
            $table->decimal('net_amount', 10, 2)->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('musoftware_payments', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_amount', 'net_amount']);
        });
    }
};
