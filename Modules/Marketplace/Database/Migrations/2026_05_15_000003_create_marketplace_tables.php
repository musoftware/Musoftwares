<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        // Service Categories
        if (! Schema::hasTable('marketplace_service_categories')) {
            Schema::create('marketplace_service_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Services
        if (! Schema::hasTable('marketplace_services')) {
            Schema::create('marketplace_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('marketplace_service_categories')->nullOnDelete();
                $table->string('title');
                $table->text('description');
                $table->string('status'); // draft, active, paused, banned
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Packages
        if (! Schema::hasTable('marketplace_packages')) {
            Schema::create('marketplace_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('marketplace_services')->cascadeOnDelete();
                $table->string('name');
                $table->text('description');
                $table->decimal('price', 20, 8);
                $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
                $table->integer('delivery_days');
                $table->timestamps();
            });
        }

        // Orders
        if (! Schema::hasTable('marketplace_orders')) {
            Schema::create('marketplace_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('marketplace_packages')->cascadeOnDelete();

                $table->decimal('amount', 20, 8);
                $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
                $table->decimal('business_amount', 20, 8)->nullable();
                $table->foreignId('business_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
                $table->decimal('commission_amount', 20, 8);

                $table->string('status'); // pending, processing, delivered, completed, cancelled, disputed
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->timestamps();
            });
        }

        // Reviews
        if (! Schema::hasTable('marketplace_reviews')) {
            Schema::create('marketplace_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
                $table->integer('rating'); // 1-5
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_packages');
        Schema::dropIfExists('marketplace_services');
        Schema::dropIfExists('marketplace_service_categories');
    }
};
