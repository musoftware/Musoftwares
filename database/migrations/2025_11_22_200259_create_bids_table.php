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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_listing_id');
            $table->foreign('job_listing_id')->references('id')->on('job_listings')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable(); // Null for anonymous users
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('name')->nullable(); // For anonymous users
            $table->string('email')->nullable(); // For anonymous users
            $table->string('phone')->nullable(); // For anonymous users
            $table->decimal('price', 10, 2);
            $table->text('description');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('job_listing_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
