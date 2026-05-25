<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_wa_templates')) {
            Schema::create('booking_wa_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                
                $table->string('name');
                $table->text('body');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_wa_confirmations')) {
            Schema::create('booking_wa_confirmations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('booking_id')->index();
                
                // pending, sent, delivered, read, failed
                $table->string('status')->default('pending')->index();
                
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_wa_action_tokens')) {
            Schema::create('booking_wa_action_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('confirmation_id')->index();
                
                $table->string('token_hash')->unique();
                // confirm, cancel, reschedule
                $table->string('action_type');
                
                $table->timestamp('expires_at')->index();
                $table->timestamp('used_at')->nullable();
                
                $table->timestamps();

                $table->foreign('confirmation_id')->references('id')->on('booking_wa_confirmations')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('booking_wa_logs')) {
            Schema::create('booking_wa_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('confirmation_id')->index();
                
                $table->string('event_type');
                $table->json('payload')->nullable();
                
                $table->timestamps();

                $table->foreign('confirmation_id')->references('id')->on('booking_wa_confirmations')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_wa_logs');
        Schema::dropIfExists('booking_wa_action_tokens');
        Schema::dropIfExists('booking_wa_confirmations');
        Schema::dropIfExists('booking_wa_templates');
    }
};
