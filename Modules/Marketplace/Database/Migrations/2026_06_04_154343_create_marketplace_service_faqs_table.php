<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_service_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('marketplace_services')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_service_faqs');
    }
};
