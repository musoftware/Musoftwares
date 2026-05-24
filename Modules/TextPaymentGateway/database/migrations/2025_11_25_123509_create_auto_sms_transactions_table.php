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
        Schema::create('auto_sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('device_id')->unsigned();
            $table->foreign('device_id')->references('id')->on('auto_sms_devices')->onDelete('cascade');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('EGP');
            $table->string('sender', 255);
            $table->string('reference')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->text('sms_message');
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('transaction_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_sms_transactions');
    }
};
