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
        Schema::create('booking_custom_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('domain')->unique();
            $table->enum('status', ['pending', 'verifying', 'verified', 'failed', 'disabled'])->default('pending')->index();
            $table->enum('ssl_status', ['pending', 'active', 'failed'])->default('pending');
            $table->string('verification_token')->nullable(); // Unique string user needs to put in TXT record
            $table->boolean('is_primary')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Note: we ensure one primary domain per tenant at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_custom_domains');
    }
};
