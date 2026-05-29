<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop all WhatsApp admin tables and related columns.
     *
     * Tables dropped (in FK-safe order):
     *   - whatsapp_chats
     *   - reverse_otp_callbacks
     *   - reverse_otp_verifications
     *   - whatsapp_sequence_states
     *   - whatsapp_sequence_steps
     *   - whatsapp_sequences
     *   - whatsapp_contacts
     *   - whatsapp_messages         (FK → whatsapp_channels, whatsapp_daily_batches)
     *   - whatsapp_daily_batches
     *   - whatsapp_channels
     *
     * Columns dropped from users:
     *   - whatsapp_balance_egp
     *   - whatsapp_balance_reset_date
     *   - whatsapp_number
     *   - whatsapp_lid
     *   - disable_unpaid_balance_whatsapp
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // ── 1. Drop dependent tables first ──────────────────────────────

        Schema::dropIfExists('whatsapp_chats');

        Schema::dropIfExists('reverse_otp_callbacks');
        Schema::dropIfExists('reverse_otp_verifications');

        // Old automation tables (replaced by generic sequences)
        Schema::dropIfExists('whatsapp_sequence_states');
        Schema::dropIfExists('whatsapp_sequence_steps');
        Schema::dropIfExists('whatsapp_sequences');

        Schema::dropIfExists('whatsapp_contacts');

        // messages FK → channels + daily_batches
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_daily_batches');

        // channels is the root
        Schema::dropIfExists('whatsapp_channels');

        // ── 2. Drop WhatsApp columns from users ──────────────────────────

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_whatsapp_lid_index');
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_whatsapp_balance_egp_index');
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_whatsapp_balance_reset_date_index');
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'whatsapp_balance_egp',
                'whatsapp_balance_reset_date',
                'whatsapp_number',
                'whatsapp_lid',
                'disable_unpaid_balance_whatsapp',
            ];

            // Only drop indexes / columns that actually exist
            if (Schema::hasColumn('users', 'whatsapp_balance_egp') && \Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                // Drop indexes if they exist (ignore errors via try/catch)
                try { $table->dropIndex(['whatsapp_balance_egp']); } catch (\Throwable) {}
                try { $table->dropIndex(['whatsapp_balance_reset_date']); } catch (\Throwable) {}
            }

            $existing = array_filter($columns, fn($c) => Schema::hasColumn('users', $c));

            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migration (restore structure only — data cannot be recovered).
     */
    public function down(): void
    {
        // Restore whatsapp_channels
        Schema::create('whatsapp_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('status')->default('disconnected');
            $table->string('channel_type')->default('whatsapp');
            $table->text('qr_code')->nullable();
            $table->timestamps();
        });

        // Restore whatsapp_daily_batches
        Schema::create('whatsapp_daily_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('batch_date');
            $table->unsignedInteger('message_count')->default(0);
            $table->decimal('cost_egp', 10, 2)->default(0);
            $table->timestamps();
        });

        // Restore whatsapp_messages
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('whatsapp_channels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('daily_batch_id')->nullable();
            $table->string('to_phone');
            $table->text('message_body');
            $table->string('status')->default('pending');
            $table->string('message_id')->nullable();
            $table->decimal('cost_egp', 10, 4)->default(0);
            $table->timestamps();

            $table->foreign('daily_batch_id')
                  ->references('id')->on('whatsapp_daily_batches')
                  ->onDelete('set null');
        });

        // Restore whatsapp_contacts
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('whatsapp_channels')->onDelete('cascade');
            $table->string('phone');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Restore users columns
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('whatsapp_balance_egp', 10, 2)->default(0)->after('currency_id');
            $table->timestamp('whatsapp_balance_reset_date')->nullable()->after('whatsapp_balance_egp');
            $table->string('whatsapp_number')->nullable();
            $table->string('whatsapp_lid')->nullable();
            $table->boolean('disable_unpaid_balance_whatsapp')->default(false);
        });
    }
};
