<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_pos_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('affiliate_pos_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('cart_id')->constrained('affiliate_pos_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('affiliate_pos_products')->cascadeOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained('affiliate_pos_product_skus')->nullOnDelete();
            $table->integer('qty')->default(1);
            $table->double('price', 10, 2)->default(0);
            $table->double('commission', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_pos_cart_items');
        Schema::dropIfExists('affiliate_pos_carts');
    }
};
