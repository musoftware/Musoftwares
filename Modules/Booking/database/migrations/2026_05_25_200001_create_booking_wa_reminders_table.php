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
        Schema::create('booking_wa_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            
            $table->string('status')->default('pending')->index(); // pending, sent, failed
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('sent_at')->nullable();
            
            $table->string('phone');
            $table->text('message');
            $table->text('error_log')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys can be added, assuming bookings table exists
            // $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            // $table->foreign('template_id')->references('id')->on('booking_wa_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_wa_reminders');
    }
};
