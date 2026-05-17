<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add paid_amount column to support partial payments
        // Recovered from old project: Invoice.paid column used for tracking partial payments
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'paid_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
