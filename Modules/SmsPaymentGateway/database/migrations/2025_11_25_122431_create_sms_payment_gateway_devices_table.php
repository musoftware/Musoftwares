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
        Schema::create('sms_payment_gateway_devices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('device_token', 255)->nullable()->unique();
            $table->string('device_name')->nullable();
            $table->string('connection_code', 64)->nullable()->unique();
            $table->timestamp('connection_code_expires_at')->nullable();
            $table->enum('status', ['pending', 'connected', 'disconnected'])->default('pending');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('phone_number')->nullable();
            $table->integer('sim_slot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('device_token');
            $table->index('connection_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_payment_gateway_devices');
    }
};
