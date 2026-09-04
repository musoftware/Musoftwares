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
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_invoices', 'cost')) {
                $table->decimal('cost', 15, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('recurring_invoices', 'days_before')) {
                $table->unsignedInteger('days_before')->default(3)->after('recurring_times_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('recurring_invoices', 'cost')) {
                $table->dropColumn('cost');
            }
            if (Schema::hasColumn('recurring_invoices', 'days_before')) {
                $table->dropColumn('days_before');
            }
        });
    }
};
