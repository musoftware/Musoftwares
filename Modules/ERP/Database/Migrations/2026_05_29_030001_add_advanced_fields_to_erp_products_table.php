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
        Schema::table('erp_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('erp_product_categories')->nullOnDelete();
            $table->string('barcode')->nullable();
            $table->string('uom')->default('piece'); // Unit of measure (e.g. piece, kg, box)
            $table->string('image_path')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'barcode', 'uom', 'image_path', 'tax_rate']);
        });
    }
};
