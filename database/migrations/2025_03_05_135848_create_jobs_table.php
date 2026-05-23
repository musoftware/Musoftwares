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
        Schema::create('jobs_and_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->integer('points')->default(0); // Points for completing the job
            $table->integer('user_limit')->default(0); // Maximum users allowed to sign up
            $table->integer('required_rank')->default(1); // Maximum users allowed to sign up
            $table->enum('completion_policy', ['once', 'multiple'])->default('once');

            $table->bigInteger('points_balance')->default(0); // Points for completing the job

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
