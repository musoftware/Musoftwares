<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Reseller Accounts ────────────────────────────────────────────────────
        Schema::create('tool_resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique(); // iframe embed token
            $table->decimal('balance', 12, 2)->default(0);
            $table->foreignId('currency_id')->default(1)->constrained('currencies');
            $table->string('status', 20)->default('active'); // active|suspended|inactive
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // ─── Reseller Sub-Users ───────────────────────────────────────────────────
        Schema::create('tool_reseller_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('tool_resellers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // active|suspended|suspended_by_reseller|sharing_flagged

            // ── Concurrent-Session Anti-Sharing Protection ─────────────────────
            // We detect fraud by checking if the SAME account is active from 2+
            // different IPs at the same time (within CONCURRENT_WINDOW minutes).
            // Dynamic IPs are fine — the old session expires naturally.
            $table->boolean('sharing_check_enabled')->default(true);     // admin can disable per user
            $table->boolean('is_sharing_flagged')->default(false);       // currently caught sharing
            $table->string('flagged_ips', 512)->nullable();              // JSON: the concurrent IPs that triggered the flag
            $table->timestamp('sharing_flagged_at')->nullable();

            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['reseller_id', 'user_id']);
            $table->index(['reseller_id', 'status']);
            $table->index('is_sharing_flagged');
        });

        // ─── Active Session Heartbeats (for concurrent detection) ─────────────────
        // Each tool page ping updates last_seen_at. We query: "how many DISTINCT
        // IPs are active for this user_id in the last N minutes?"
        // If > 1  →  sharing detected.
        Schema::create('reseller_user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_user_id')->constrained('tool_reseller_users')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();

            // One row per (user, IP) — upserted on every request
            $table->unique(['reseller_user_id', 'ip_address']);
            $table->index(['reseller_user_id', 'last_seen_at']);
        });

        // ─── Reseller Transaction Ledger ──────────────────────────────────────────
        Schema::create('tool_reseller_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('tool_resellers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30); // top_up|charge|manual_credit|manual_debit|suspension
            $table->decimal('amount', 12, 2);       // positive = credit, negative = debit
            $table->decimal('balance_after', 12, 2); // balance snapshot after transaction
            $table->foreignId('currency_id')->default(1)->constrained('currencies');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();  // subscription_id, etc.
            $table->timestamps();

            $table->index(['reseller_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_reseller_transactions');
        Schema::dropIfExists('reseller_user_sessions');
        Schema::dropIfExists('tool_reseller_users');
        Schema::dropIfExists('tool_resellers');
    }
};
