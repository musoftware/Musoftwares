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
        Schema::create('coworker_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->string('phone_number');
            $table->text('message');
            $table->unsignedBigInteger('channel_id');
            $table->string('status')->default('pending'); // pending, sending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coworker_messages');
    }
};
