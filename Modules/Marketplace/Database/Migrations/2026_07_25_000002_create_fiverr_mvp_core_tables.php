<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Order Snapshot column
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_orders', 'snapshot')) {
                $table->json('snapshot')->nullable()->after('package_id');
            }
        });

        // 2. Order Status History & Timeline
        if (!Schema::hasTable('marketplace_order_status_histories')) {
            Schema::create('marketplace_order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->string('old_status')->nullable();
                $table->string('new_status');
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 3. Attachments Entity (Unified for Messages, Deliveries, Requirements)
        if (!Schema::hasTable('marketplace_attachments')) {
            Schema::create('marketplace_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->nullableMorphs('attachable');
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->timestamps();
            });
        }

        // 4. Custom Offers
        if (!Schema::hasTable('marketplace_custom_offers')) {
            Schema::create('marketplace_custom_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained('marketplace_services')->nullOnDelete();
                $table->foreignId('package_id')->nullable()->constrained('marketplace_packages')->nullOnDelete();
                $table->text('description');
                $table->decimal('price', 20, 8);
                $table->integer('delivery_days')->default(1);
                $table->integer('revisions')->default(1);
                $table->string('status')->default('pending'); // pending, accepted, rejected, expired, cancelled
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Seller Availability on Users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'seller_availability')) {
                $table->string('seller_availability')->default('available'); // available, away, vacation
            }
            if (!Schema::hasColumn('users', 'vacation_message')) {
                $table->text('vacation_message')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_custom_offers');
        Schema::dropIfExists('marketplace_attachments');
        Schema::dropIfExists('marketplace_order_status_histories');

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['seller_availability', 'vacation_message']);
        });
    }
};
