<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action. Nullable so the same table can record
            // unauthenticated/automated actions (cron, webhook self-signed).
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Network attribution for the actor request.
            $table->string('actor_ip', 45)->nullable();
            $table->string('actor_user_agent', 512)->nullable();

            // Machine-readable verb + human-readable label.
            // Examples: user.login_as, user.reset_password, broadcast.send_global,
            // settings.update, transaction.transfer, serial_device.register.
            $table->string('action', 96)->index();
            $table->string('severity', 16)->default('info'); // info|warning|critical

            // Polymorphic target. target_id stays a string because some targets
            // (e.g. payment gateways) are referenced by external identifiers.
            $table->string('target_type', 64)->nullable();
            $table->string('target_id', 96)->nullable();

            // Free-form structured detail (jsonb on PG; JSON on MySQL/SQLite).
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
