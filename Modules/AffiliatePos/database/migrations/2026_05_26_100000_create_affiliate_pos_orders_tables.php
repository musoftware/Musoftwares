<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_pos_shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('affiliate_pos_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_phone2')->nullable();
            $table->string('customer_email')->nullable();
            
            $table->string('customer_address');
            $table->string('customer_city')->nullable();
            $table->integer('customer_city_id')->nullable();
            $table->integer('customer_governorate_id')->nullable();
            $table->string('customer_governorate')->nullable();
            
            $table->timestamps();
        });

        Schema::create('affiliate_pos_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('moderator_name')->nullable();
            
            $table->string('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_phone2')->nullable();
            $table->string('customer_email')->nullable();
            
            $table->string('customer_address')->nullable();
            $table->string('customer_city')->nullable();
            $table->integer('customer_city_id')->nullable();
            $table->integer('customer_governorate_id')->nullable();
            $table->string('customer_governorate')->nullable();
            
            $table->integer('partial_delivery')->default(1);
            $table->string('note_value')->nullable();
            $table->date('order_date')->nullable();
            
            $table->double('subtotal', 10, 2)->default(0);
            $table->double('commission', 10, 2)->default(0);
            $table->double('delivery', 10, 2)->default(0);
            $table->integer('replacing')->default(0);
            $table->double('total', 10, 2)->default(0);
            
            $table->string('status', 50)->default('new');
            $table->string('unique_id', 255)->unique();
            
            $table->foreignId('shipping_company_id')->nullable()->constrained('affiliate_pos_shipping_companies')->nullOnDelete();
            
            $table->timestamps();
        });

        Schema::create('affiliate_pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('order_id')->constrained('affiliate_pos_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Affiliate
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('affiliate_pos_products')->nullOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained('affiliate_pos_product_skus')->nullOnDelete();
            
            $table->double('price', 10, 2)->default(0);
            $table->double('commission', 10, 2)->default(0);
            $table->double('website_commission', 10, 2)->default(0);
            $table->integer('qty')->unsigned()->default(1);
            $table->double('total', 10, 2)->default(0);
            $table->double('total_commission', 10, 2)->default(0);
            
            $table->integer('generated_transaction')->default(0);
            $table->string('status', 50)->default('new');
            
            $table->timestamps();
        });

        Schema::create('affiliate_pos_order_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('order_id')->constrained('affiliate_pos_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_item_id')->constrained('affiliate_pos_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('affiliate_pos_products')->nullOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained('affiliate_pos_product_skus')->nullOnDelete();
            
            $table->double('price', 10, 2)->default(0);
            $table->double('commission', 10, 2)->default(0);
            $table->double('website_commission', 10, 2)->default(0);
            $table->integer('qty')->unsigned()->default(1);
            $table->double('total', 10, 2)->default(0);
            $table->double('total_commission', 10, 2)->default(0);
            
            $table->integer('generated_transaction')->default(0);
            $table->string('status', 50)->default('returning');
            
            $table->timestamps();
        });

        Schema::create('affiliate_pos_order_replaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('order_id')->constrained('affiliate_pos_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignId('order_item_id')->constrained('affiliate_pos_order_items')->cascadeOnDelete();
            $table->foreignId('new_order_item_id')->nullable()->constrained('affiliate_pos_order_items')->nullOnDelete();
            
            $table->string('status', 50)->default('replacing');
            $table->timestamps();
        });

        Schema::create('affiliate_pos_order_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('order_id')->constrained('affiliate_pos_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('comment');
            $table->timestamps();
        });
        
        Schema::create('affiliate_pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('relation'); // Used for associating with an Order, Payment, etc.
            $table->string('type', 50)->nullable(); // withdrawal, deposit, commission
            $table->double('amount', 12, 2)->default(0);
            $table->double('balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_pos_transactions');
        Schema::dropIfExists('affiliate_pos_order_comments');
        Schema::dropIfExists('affiliate_pos_order_replaces');
        Schema::dropIfExists('affiliate_pos_order_returns');
        Schema::dropIfExists('affiliate_pos_order_items');
        Schema::dropIfExists('affiliate_pos_orders');
        Schema::dropIfExists('affiliate_pos_addresses');
        Schema::dropIfExists('affiliate_pos_shipping_companies');
    }
};
