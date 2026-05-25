<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->integer('duration')->default(30); // in minutes
            $table->decimal('price', 10, 2)->default(0);
            $table->string('color')->nullable();
            $table->integer('buffer_time')->default(0); // overall service buffer
            $table->integer('capacity')->default(1);
            $table->boolean('is_group_session')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_services');
    }
};
