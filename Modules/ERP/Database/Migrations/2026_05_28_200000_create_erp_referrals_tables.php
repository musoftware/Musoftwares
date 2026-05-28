<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('erp_tenant_clients', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedBigInteger('referred_by')->nullable()->index();
            
            $table->foreign('referred_by')->references('id')->on('erp_tenant_clients')->nullOnDelete();
        });

        Schema::create('erp_client_referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referee_id');
            $table->tinyInteger('level')->default(1);
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('business_amount', 15, 2);
            $table->foreignId('business_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');
            $table->decimal('commission_rate', 5, 2);
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('erp_tenant_clients')->cascadeOnDelete();
            $table->foreign('referee_id')->references('id')->on('erp_tenant_clients')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('erp_client_referral_earnings');

        Schema::table('erp_tenant_clients', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
