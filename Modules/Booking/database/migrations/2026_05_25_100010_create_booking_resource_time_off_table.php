<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_resource_time_off', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->index();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('type')->default('block'); // block, break, personal
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('resource_id')->references('id')->on('booking_resources')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_resource_time_off');
    }
};
