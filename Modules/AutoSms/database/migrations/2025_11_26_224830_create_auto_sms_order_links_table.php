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
        Schema::create('auto_sms_order_links', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_id');
            $table->string('phone_number', 20);
            $table->enum('status', ['pending', 'matched'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('phone_number');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'phone_number', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_sms_order_links');
    }
};
