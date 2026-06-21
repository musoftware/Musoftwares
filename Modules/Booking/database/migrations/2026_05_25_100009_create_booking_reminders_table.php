<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id')->index();
            $table->string('type'); // whatsapp, sms, email, push
            $table->dateTime('send_at');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reservation_id')->references('id')->on('booking_reservations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reminders');
    }
};
