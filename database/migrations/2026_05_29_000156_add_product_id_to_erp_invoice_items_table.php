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
        if (Schema::hasTable('erp_invoice_items')) {
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('erp_invoice_items', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->constrained('erp_products')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_invoice_items')) {
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('erp_invoice_items', 'product_id')) {
                    $table->dropForeign(['product_id']);
                    $table->dropColumn('product_id');
                }
            });
        }
    }
};
