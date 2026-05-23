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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('tech_required')->nullable(); // Array of required technologies
            $table->enum('status', ['open', 'closed', 'awarded'])->default('open');
            $table->unsignedBigInteger('created_by')->nullable(); // Admin or user who posted
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('awarded_to')->nullable(); // User who won the bid
            $table->foreign('awarded_to')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('deadline')->nullable(); // Job deadline
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->integer('bids_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
