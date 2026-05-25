<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->string('rule_type'); // e.g. except_holidays, specific_dates
            $table->json('rule_data')->nullable(); // holds complex conditions
            $table->timestamps();
            
            $table->foreign('resource_id')->references('id')->on('booking_resources')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_schedule_rules');
    }
};
