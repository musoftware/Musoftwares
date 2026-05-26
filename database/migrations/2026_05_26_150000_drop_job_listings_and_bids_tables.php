<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the legacy job_listings, bids, and job_listing_translations tables.
     * These were replaced by the Freelance module (freelance_jobs, freelance_proposals, freelance_contracts).
     */
    public function up(): void
    {
        // Drop in correct order: child tables first, then parents
        Schema::dropIfExists('job_listing_translations');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('job_listings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate job_listings
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('tech_required')->nullable();
            $table->enum('status', ['open', 'closed', 'awarded'])->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('awarded_to')->nullable();
            $table->foreign('awarded_to')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('deadline')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->integer('bids_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('created_at');
        });

        // Recreate bids
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_listing_id');
            $table->foreign('job_listing_id')->references('id')->on('job_listings')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country', 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index('job_listing_id');
        });

        // Recreate job_listing_translations
        Schema::create('job_listing_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->onDelete('cascade');
            $table->string('locale', 10);
            $table->string('field');
            $table->text('value');
            $table->timestamps();
            $table->index(['job_listing_id', 'locale', 'field']);
            $table->unique(['job_listing_id', 'locale', 'field'], 'job_listing_translations_unique');
        });
    }
};
