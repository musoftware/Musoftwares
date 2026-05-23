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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->text('bio');
            $table->string('image')->nullable();
            $table->integer('experience_years')->default(0);
            $table->integer('projects_count')->default(0);
            $table->json('skills')->nullable();
            $table->json('social_links')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->json('achievements')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
