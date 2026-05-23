<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Comment thread for admin ↔ freelancer communication on a task.
     */
    public function up(): void
    {
        Schema::create('freelancer_task_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('freelancer_task_id')->index();

            // Who wrote the comment (admin or freelancer)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->text('body');

            $table->timestamps();

            // Index for efficient loading of comment threads
            $table->index(['freelancer_task_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer_task_comments');
    }
};
