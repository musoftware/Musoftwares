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
        // 1. Create whatsapp_businesses table
        Schema::create('whatsapp_businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->decimal('wallet_balance', 12, 4)->default(0.0000);
            $table->string('currency', 10)->default('USD');
            $table->decimal('per_message_fee', 8, 4)->default(0.0010);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Add whatsapp_business_id to whatsapp_accounts
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->foreignId('whatsapp_business_id')
                ->nullable()
                ->after('user_id')
                ->constrained('whatsapp_businesses')
                ->onDelete('cascade');
        });

        // 3. Create whatsapp_transactions table
        Schema::create('whatsapp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_business_id')->constrained('whatsapp_businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['credit_recharge', 'debit_message_fee', 'refund']);
            $table->decimal('amount', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('description');
            $table->string('reference_id')->nullable();
            $table->timestamps();
        });

        // 4. Add whatsapp_business_id and cost_charged to whatsapp_logs
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->foreignId('whatsapp_business_id')
                ->nullable()
                ->after('whatsapp_account_id')
                ->constrained('whatsapp_businesses')
                ->onDelete('set null');

            $table->decimal('cost_charged', 8, 4)
                ->default(0.0000)
                ->after('recipient_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_business_id']);
            $table->dropColumn(['whatsapp_business_id', 'cost_charged']);
        });

        Schema::dropIfExists('whatsapp_transactions');

        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_business_id']);
            $table->dropColumn('whatsapp_business_id');
        });

        Schema::dropIfExists('whatsapp_businesses');
    }
};
