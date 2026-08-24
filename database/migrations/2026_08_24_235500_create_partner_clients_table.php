<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partner_clients')) {
            Schema::create('partner_clients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('client_name', 100);
                $table->string('client_key', 64)->unique();
                $table->string('client_secret', 128);
                $table->decimal('wallet_balance', 14, 4)->default(0.0000);
                $table->enum('pricing_model', ['PAYG_PER_MSG', 'SUBSCRIPTION', 'HYBRID'])->default('PAYG_PER_MSG');
                $table->decimal('cost_per_message', 8, 4)->default(0.0100);
                $table->decimal('low_balance_threshold', 8, 2)->default(10.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('user_id');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('partner_credit_leases')) {
            Schema::create('partner_credit_leases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_client_id')->constrained('partner_clients')->cascadeOnDelete();
                $table->string('lease_id', 64)->unique();
                $table->integer('granted_messages')->default(500);
                $table->integer('settled_messages')->default(0);
                $table->decimal('reserved_amount', 12, 4)->default(5.0000);
                $table->decimal('final_charged_amount', 12, 4)->default(0.0000);
                $table->enum('status', ['ACTIVE', 'SETTLED', 'EXPIRED', 'CANCELLED'])->default('ACTIVE');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['partner_client_id', 'status']);
                $table->index('expires_at');
            });
        }

        if (!Schema::hasTable('partner_usage_logs')) {
            Schema::create('partner_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_client_id')->constrained('partner_clients')->cascadeOnDelete();
                $table->string('lease_id', 64)->nullable()->index();
                $table->enum('type', ['TOP_UP', 'LEASE_RESERVE', 'LEASE_SETTLE', 'ADJUSTMENT']);
                $table->decimal('amount', 14, 4);
                $table->decimal('balance_after', 14, 4);
                $table->string('description', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['partner_client_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_usage_logs');
        Schema::dropIfExists('partner_credit_leases');
        Schema::dropIfExists('partner_clients');
    }
};
