<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('module_plans')) {
            Schema::create('module_plans', function (Blueprint $table) {
                $table->id();
                $table->string('module');          // erp, freelance, marketing, booking, etc.
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->string('billing')->default('monthly'); // monthly, yearly
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            $table->softDeletes();

                $table->index('module');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('object');
                $table->string('status')->default('active');  // active, expired, cancelled
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('auto_renew')->default(true);
                $table->timestamps();
            $table->softDeletes();

                $table->index(['user_id', 'status']);
                $table->index('object');
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('module_plans');
    }
};
