<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable(); // vacation, holiday, emergency, break, sick_leave
            $table->time('start_time')->nullable(); // null means whole day
            $table->time('end_time')->nullable(); // null means whole day
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('resource_id')->references('id')->on('booking_resources')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_schedule_exceptions');
    }
};
