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
        // 1. Categories Table
        Schema::create('affiliate_pos_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('affiliate_pos_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('show_on_menu')->default(true);
            $table->text('image')->nullable();
            $table->text('big_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Products Table
        Schema::create('affiliate_pos_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Vendor / Creator
            $table->foreignId('category_id')->nullable()->constrained('affiliate_pos_categories')->nullOnDelete();
            
            $table->string('name');
            $table->string('code')->nullable()->index();
            
            $table->decimal('price', 12, 2);
            $table->decimal('old_price', 12, 2)->nullable();
            $table->integer('min_qty')->default(1);
            
            $table->decimal('commission', 12, 2)->default(0); // Vendor Commission
            $table->decimal('website_commission', 12, 2)->default(0); // Platform Commission
            
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->text('google_drive_link')->nullable();
            $table->text('zipfile')->nullable();
            
            $table->enum('type', ['simple', 'variant'])->default('simple');
            $table->enum('status', ['pending', 'submitted', 'active', 'declined'])->default('active');
            
            $table->boolean('is_hidden')->default(false);
            $table->boolean('most_sales')->default(false);
            $table->integer('sales_count')->default(0);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Product Images Table
        Schema::create('affiliate_pos_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('affiliate_pos_products')->cascadeOnDelete();
            $table->string('filename');
            $table->string('filename_540x600')->nullable();
            $table->string('filename_150x160')->nullable();
            $table->string('filename_810x900')->nullable();
            $table->timestamps();
        });

        // 4. Product Options Table
        Schema::create('affiliate_pos_product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('affiliate_pos_products')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // 5. Product Option Values Table
        Schema::create('affiliate_pos_product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('affiliate_pos_product_options')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('affiliate_pos_products')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });

        // 6. Product SKUs Table
        Schema::create('affiliate_pos_product_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('affiliate_pos_products')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('code')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_hidden')->default(false);
            $table->integer('sales_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. Product SKU Values Table
        Schema::create('affiliate_pos_product_sku_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('affiliate_pos_product_skus')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('affiliate_pos_product_options')->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained('affiliate_pos_product_option_values')->cascadeOnDelete();
            $table->timestamps();
        });

        // 8. Tags Table
        Schema::create('affiliate_pos_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        // 9. Taggables Table
        Schema::create('affiliate_pos_taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('affiliate_pos_tags')->cascadeOnDelete();
            $table->morphs('taggable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_pos_taggables');
        Schema::dropIfExists('affiliate_pos_tags');
        Schema::dropIfExists('affiliate_pos_product_sku_values');
        Schema::dropIfExists('affiliate_pos_product_skus');
        Schema::dropIfExists('affiliate_pos_product_option_values');
        Schema::dropIfExists('affiliate_pos_product_options');
        Schema::dropIfExists('affiliate_pos_product_images');
        Schema::dropIfExists('affiliate_pos_products');
        Schema::dropIfExists('affiliate_pos_categories');
    }
};
