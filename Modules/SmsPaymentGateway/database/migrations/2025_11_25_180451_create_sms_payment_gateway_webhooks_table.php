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
        Schema::create('sms_payment_gateway_webhooks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('webhook_url', 500);
            $table->string('webhook_secret')->nullable(); // For signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_payment_gateway_webhooks');
    }
};
