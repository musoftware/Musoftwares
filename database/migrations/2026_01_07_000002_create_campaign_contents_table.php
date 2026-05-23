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
        Schema::create('campaign_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->enum('language', ['en', 'ar'])->default('en');
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable();
            $table->text('whatsapp_message')->nullable();
            $table->timestamps();

            // Ensure unique language per campaign
            $table->unique(['campaign_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_contents');
    }
};
