<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('wallet_phone_number')->nullable();
            $table->boolean('is_instapay_enabled')->default(true);
            $table->boolean('is_vodafone_cash_enabled')->default(true);
            $table->timestamps();

            // Foreign keys if necessary
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_payment_gateway_settings');
    }
};
