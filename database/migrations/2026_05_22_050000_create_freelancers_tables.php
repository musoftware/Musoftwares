<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('facebook')->nullable();
            $table->string('linked_in')->nullable();
            $table->string('whatsapp')->nullable();
            $table->integer('time_from')->nullable();
            $table->integer('time_to')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('platform_freelancer_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('platform_freelancers')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('platform_skills')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_freelancer_skill');
        Schema::dropIfExists('platform_skills');
        Schema::dropIfExists('platform_freelancers');
    }
};
