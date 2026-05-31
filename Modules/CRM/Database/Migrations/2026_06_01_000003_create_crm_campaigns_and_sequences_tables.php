<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('embed_token')->nullable();
            $table->string('form_title')->nullable();
            $table->text('form_description')->nullable();
            $table->string('button_text')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('target_audience')->nullable();
            $table->json('filter_criteria')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('total_recipients')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('campaign_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('language_used')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('delay')->default(0);
            $table->string('unit')->default('days');
            $table->integer('order')->default(0);
            $table->boolean('send_email')->default(false);
            $table->json('email_subject')->nullable();
            $table->json('email_content')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sequence_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable');
            $table->string('status')->default('active');
            $table->integer('current_step_order')->default(0);
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_states');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('campaign_lead');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaign_contents');
        Schema::dropIfExists('campaigns');
    }
};
