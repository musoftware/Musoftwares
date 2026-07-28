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
        // 1. Add bot_reply_fee to whatsapp_businesses
        if (Schema::hasTable('whatsapp_businesses')) {
            Schema::table('whatsapp_businesses', function (Blueprint $table) {
                if (!Schema::hasColumn('whatsapp_businesses', 'bot_reply_fee')) {
                    $table->decimal('bot_reply_fee', 12, 4)->default(0.0005)->after('per_message_fee');
                }
            });
        }

        // 2. Create telegram_subscriber_groups
        if (!Schema::hasTable('telegram_subscriber_groups')) {
            Schema::create('telegram_subscriber_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('telegram_bot_id')->constrained('telegram_bots')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['telegram_bot_id']);
            });
        }

        // 3. Create telegram_subscribers
        if (!Schema::hasTable('telegram_subscribers')) {
            Schema::create('telegram_subscribers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('telegram_bot_id')->constrained('telegram_bots')->onDelete('cascade');
                $table->foreignId('telegram_subscriber_group_id')
                    ->nullable()
                    ->constrained('telegram_subscriber_groups')
                    ->onDelete('set null');
                $table->string('chat_id');
                $table->string('username')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();

                $table->unique(['telegram_bot_id', 'chat_id']);
                $table->index(['telegram_subscriber_group_id']);
            });
        }

        // 4. Create bot_flows
        if (!Schema::hasTable('bot_flows')) {
            Schema::create('bot_flows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_business_id')->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->string('channel')->default('whatsapp'); // whatsapp, telegram
                $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->onDelete('cascade');
                $table->string('name');
                $table->boolean('is_active')->default(false);
                $table->string('trigger_type')->default('keyword'); // keyword, start_bot, default
                $table->json('trigger_keywords')->nullable();
                $table->json('nodes')->nullable();
                $table->json('edges')->nullable();
                $table->timestamps();

                $table->index(['whatsapp_business_id', 'channel']);
                $table->index(['telegram_bot_id']);
            });
        }

        // 5. Create bot_flow_sessions
        if (!Schema::hasTable('bot_flow_sessions')) {
            Schema::create('bot_flow_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('channel'); // whatsapp, telegram
                $table->foreignId('whatsapp_business_id')->nullable()->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->onDelete('cascade');
                $table->string('subscriber_identifier'); // Chat ID for Telegram, Phone for WhatsApp
                $table->foreignId('bot_flow_id')->constrained('bot_flows')->onDelete('cascade');
                $table->string('current_node_id');
                $table->json('context_data')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['channel', 'subscriber_identifier']);
                $table->index(['bot_flow_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_flow_sessions');
        Schema::dropIfExists('bot_flows');
        Schema::dropIfExists('telegram_subscribers');
        Schema::dropIfExists('telegram_subscriber_groups');

        if (Schema::hasTable('whatsapp_businesses')) {
            Schema::table('whatsapp_businesses', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_businesses', 'bot_reply_fee')) {
                    $table->dropColumn('bot_reply_fee');
                }
            });
        }
    }
};
