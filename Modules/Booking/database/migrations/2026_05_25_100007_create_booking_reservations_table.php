<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('resource_id')->index();
            $table->unsignedBigInteger('service_id')->index();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed, no_show, rescheduled
            $table->text('notes')->nullable();
            $table->string('source')->nullable(); // manual, website, whatsapp, facebook, api
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('booking_customers')->onDelete('cascade');
            $table->foreign('resource_id')->references('id')->on('booking_resources')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('booking_services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reservations');
    }
};
