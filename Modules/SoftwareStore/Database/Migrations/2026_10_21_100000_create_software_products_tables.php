<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('software_products')) {
            Schema::create('software_products', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name_en');
                $table->string('name_ar');
                $table->string('tagline_en')->nullable();
                $table->string('tagline_ar')->nullable();
                $table->text('description_en')->nullable();
                $table->text('description_ar')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('currency', 3)->default('EGP');
                $table->string('image_path')->nullable();
                $table->string('video_path')->nullable();
                $table->string('video_url')->nullable();
                $table->string('poster_path')->nullable();
                $table->json('gallery')->nullable();
                $table->json('theme')->nullable();
                $table->json('landing')->nullable();
                $table->boolean('is_active')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('software_releases')) {
            Schema::create('software_releases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('software_product_id')->constrained('software_products')->cascadeOnDelete();
                $table->string('version', 50);
                $table->string('file_path');
                $table->string('file_name');
                $table->string('sha512', 200);
                $table->unsignedBigInteger('size');
                $table->string('blockmap_path')->nullable();
                $table->text('release_notes')->nullable();
                $table->boolean('is_published')->default(true);
                $table->timestamp('released_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['software_product_id', 'version']);
            });
        }

        if (! Schema::hasTable('software_orders')) {
            Schema::create('software_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('software_product_id')->constrained('software_products');
                $table->string('client_name');
                $table->string('client_email');
                $table->string('client_whatsapp', 50);
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3);
                $table->string('status', 30)->default('pending_payment');
                $table->string('payment_gateway', 30)->nullable();
                $table->string('payment_reference')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('software_orders');
        Schema::dropIfExists('software_releases');
        Schema::dropIfExists('software_products');
    }
};
