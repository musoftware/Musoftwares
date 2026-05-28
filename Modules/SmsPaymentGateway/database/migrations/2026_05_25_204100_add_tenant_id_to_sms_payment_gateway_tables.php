انا<?php

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
        // 1. sms_payment_gateway_devices
        if (Schema::hasTable('sms_payment_gateway_devices') && !Schema::hasColumn('sms_payment_gateway_devices', 'tenant_id')) {
            Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('erp_tenants')->cascadeOnDelete();
            });
        }

        // 2. sms_payment_gateway_transactions
        if (Schema::hasTable('sms_payment_gateway_transactions') && !Schema::hasColumn('sms_payment_gateway_transactions', 'tenant_id')) {
            Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('erp_tenants')->cascadeOnDelete();
            });
        }

        // 3. sms_payment_gateway_webhooks
        if (Schema::hasTable('sms_payment_gateway_webhooks') && !Schema::hasColumn('sms_payment_gateway_webhooks', 'tenant_id')) {
            Schema::table('sms_payment_gateway_webhooks', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('erp_tenants')->cascadeOnDelete();
            });
        }

        // 4. sms_payment_gateway_wallets
        if (Schema::hasTable('sms_payment_gateway_wallets') && !Schema::hasColumn('sms_payment_gateway_wallets', 'tenant_id')) {
            Schema::table('sms_payment_gateway_wallets', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('erp_tenants')->cascadeOnDelete();
            });
        }

        // 5. sms_payment_gateway_order_links
        if (Schema::hasTable('sms_payment_gateway_order_links') && !Schema::hasColumn('sms_payment_gateway_order_links', 'tenant_id')) {
            Schema::table('sms_payment_gateway_order_links', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('erp_tenants')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        
        Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        
        Schema::table('sms_payment_gateway_webhooks', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        
        Schema::table('sms_payment_gateway_wallets', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        
        Schema::table('sms_payment_gateway_order_links', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
