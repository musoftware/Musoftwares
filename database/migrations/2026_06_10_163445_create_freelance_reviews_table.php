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
        if (!Schema::hasTable('freelance_reviews')) {
            Schema::create('freelance_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
                $table->tinyInteger('rating')->unsigned(); // 1 to 5
                $table->text('comment')->nullable();
                $table->boolean('is_visible')->default(false);
                $table->timestamps();
            $table->softDeletes();

                // Prevent duplicate reviews from same person for same contract
                $table->unique(['contract_id', 'reviewer_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_reviews');
    }
};

