<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Services
        Schema::create('marketplace_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status'); // draft, active, paused, banned
            $table->softDeletes();
            $table->timestamps();
        });

        // Packages
        Schema::create('marketplace_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('marketplace_services')->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 20, 8);
            $table->string('currency_code', 3);
            $table->integer('delivery_days');
            $table->timestamps();
        });

        // Orders
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('marketplace_packages')->cascadeOnDelete();

            $table->decimal('amount', 20, 8);
            $table->string('currency_code', 3);
            $table->decimal('commission_amount', 20, 8);

            $table->string('status'); // pending, processing, delivered, completed, cancelled, disputed
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });

        // Reviews
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

    public function down()
    {
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_packages');
        Schema::dropIfExists('marketplace_services');
    }
};
