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
        if (Schema::hasTable('erp_products')) {
            $indexExists = false;
            try {
                $conn = Schema::getConnection();
                $db = $conn->getDatabaseName();
                $indexExists = collect($conn->select("
                    SELECT 1 FROM information_schema.statistics 
                    WHERE table_schema = ? 
                    AND table_name = 'erp_products' 
                    AND index_name = 'erp_products_tenant_id_sku_unique'
                ", [$db]))->isNotEmpty();
            } catch (\Exception $e) {
            }

            Schema::table('erp_products', function (Blueprint $table) use ($indexExists) {
                if (!Schema::hasColumn('erp_products', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!$indexExists) {
                    $table->unique(['tenant_id', 'sku']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_products')) {
            $indexExists = false;
            try {
                $conn = Schema::getConnection();
                $db = $conn->getDatabaseName();
                $indexExists = collect($conn->select("
                    SELECT 1 FROM information_schema.statistics 
                    WHERE table_schema = ? 
                    AND table_name = 'erp_products' 
                    AND index_name = 'erp_products_tenant_id_sku_unique'
                ", [$db]))->isNotEmpty();
            } catch (\Exception $e) {
            }

            Schema::table('erp_products', function (Blueprint $table) use ($indexExists) {
                if ($indexExists) {
                    $table->dropUnique(['tenant_id', 'sku']);
                }
                if (Schema::hasColumn('erp_products', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
