<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('micro_services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description'); // سطر بسيط خالص
            $table->decimal('price', 12, 2)->default(0); // Base price in EGP
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedInteger('delivery_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('micro_service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('micro_service_id')->constrained('micro_services')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2); // Amount in user's currency
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('base_amount', 12, 2); // Converted base amount in EGP
            $table->text('requirements'); // بيانات الطلب التي كتبها العميل
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->text('admin_notes')->nullable(); // ملاحظات الإنجاز من الأدمن
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micro_service_orders');
        Schema::dropIfExists('micro_services');
    }
};
