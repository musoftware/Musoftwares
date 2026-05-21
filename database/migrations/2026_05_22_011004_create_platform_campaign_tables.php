<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('email'); // email, whatsapp, mixed
            $table->string('target_audience')->nullable(); // lead_set_id, all_leads, active_users
            
            $table->json('email_subject')->nullable();
            $table->json('email_content')->nullable();
            $table->json('whatsapp_content')->nullable();
            
            $table->string('status')->default('draft'); // draft, scheduled, sending, paused, completed
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('platform_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('platform_campaigns')->cascadeOnDelete();
            
            $table->morphs('recipient');
            
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_campaign_recipients');
        Schema::dropIfExists('platform_campaigns');
    }
};
