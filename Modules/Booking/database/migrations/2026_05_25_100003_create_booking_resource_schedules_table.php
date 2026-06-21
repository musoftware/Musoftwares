<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_resource_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->index();
            $table->string('day_of_week'); // e.g. monday, tuesday
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('resource_id')->references('id')->on('booking_resources')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_resource_schedules');
    }
};
