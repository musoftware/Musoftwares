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
        // 1. Add uuid and client profiling fields to whatsapp_businesses
        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_businesses', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('whatsapp_businesses', 'client_name')) {
                $table->string('client_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('whatsapp_businesses', 'client_email')) {
                $table->string('client_email')->nullable()->after('client_name');
            }
            if (! Schema::hasColumn('whatsapp_businesses', 'client_mobile')) {
                $table->string('client_mobile')->nullable()->after('client_email');
            }
            if (! Schema::hasColumn('whatsapp_businesses', 'client_whatsapp')) {
                $table->string('client_whatsapp')->nullable()->after('client_mobile');
            }
        });

        // Backfill existing businesses with UUIDs
        foreach (\Modules\WhatsappSender\Models\WhatsappBusiness::all() as $biz) {
            if (empty($biz->uuid)) {
                $biz->update([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                ]);
            }
        }

        // 2. Create telegram_bots table
        if (! Schema::hasTable('telegram_bots')) {
            Schema::create('telegram_bots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_business_id')->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->string('name');
                $table->string('username')->nullable();
                $table->text('token');
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Create whatsapp_templates table
        if (! Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_business_id')->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->string('name');
                $table->string('category')->default('UTILITY');
                $table->string('language')->default('en_US');
                $table->json('components');
                $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
                $table->string('meta_template_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['whatsapp_business_id', 'status']);
            });
        }

        // 4. Create whatsapp_contact_groups table
        if (! Schema::hasTable('whatsapp_contact_groups')) {
            Schema::create('whatsapp_contact_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('whatsapp_business_id')->nullable()->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'whatsapp_business_id']);
            });
        }

        // 5. Create whatsapp_contacts table
        if (! Schema::hasTable('whatsapp_contacts')) {
            Schema::create('whatsapp_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_contact_group_id')->constrained('whatsapp_contact_groups')->onDelete('cascade');
                $table->string('name')->nullable();
                $table->string('phone');
                $table->json('custom_fields')->nullable();
                $table->timestamps();

                $table->index(['whatsapp_contact_group_id']);
            });
        }

        // 6. Create whatsapp_schedules table
        if (! Schema::hasTable('whatsapp_schedules')) {
            Schema::create('whatsapp_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('whatsapp_business_id')->nullable()->constrained('whatsapp_businesses')->onDelete('cascade');
                $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->onDelete('cascade');
                $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->onDelete('cascade');
                $table->foreignId('whatsapp_contact_group_id')->nullable()->constrained('whatsapp_contact_groups')->onDelete('cascade');
                $table->string('recipient_phone')->nullable();
                $table->string('channel')->default('whatsapp'); // whatsapp, telegram
                $table->string('message_type')->default('text'); // text, template
                $table->text('message_body')->nullable();
                $table->string('template_name')->nullable();
                $table->string('template_language')->nullable();
                $table->json('template_components')->nullable();
                $table->dateTime('scheduled_at');
                $table->string('status')->default('pending'); // pending, processing, sent, failed, cancelled
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status', 'scheduled_at']);
            });
        }

        // 7. Add telegram_bot_id and channel to whatsapp_logs
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_logs', 'channel')) {
                $table->string('channel')->default('whatsapp')->after('whatsapp_account_id');
            }
            if (! Schema::hasColumn('whatsapp_logs', 'telegram_bot_id')) {
                $table->foreignId('telegram_bot_id')
                    ->nullable()
                    ->after('whatsapp_account_id')
                    ->constrained('telegram_bots')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_logs', 'telegram_bot_id')) {
                $table->dropForeign(['telegram_bot_id']);
                $table->dropColumn('telegram_bot_id');
            }
            if (Schema::hasColumn('whatsapp_logs', 'channel')) {
                $table->dropColumn('channel');
            }
        });

        Schema::dropIfExists('whatsapp_schedules');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_contact_groups');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('telegram_bots');

        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'client_name', 'client_email', 'client_mobile', 'client_whatsapp']);
        });
    }
};
