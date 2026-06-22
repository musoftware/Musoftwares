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
        if (Schema::hasTable('platform_subscriptions')) {
            Schema::table('platform_subscriptions', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('payment_orders')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('tenant_features')) {
            Schema::table('tenant_features', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('user_integrations')) {
            Schema::table('user_integrations', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('platform_subscriptions')) {
            Schema::table('platform_subscriptions', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('payment_orders')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('tenant_features')) {
            Schema::table('tenant_features', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
        if (Schema::hasTable('user_integrations')) {
            Schema::table('user_integrations', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->change();
            });
        }
    }
};
