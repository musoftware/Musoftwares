<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_consents_log')) {
            Schema::create('user_consents_log', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_identifier', 64)->nullable()->index();
                $table->string('consent_type', 50)->index();
                $table->string('action', 20);
                $table->string('policy_version', 32);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'consent_type', 'id'], 'idx_user_consent_latest');
            });
        }

        if (! Schema::hasTable('data_subject_requests')) {
            Schema::create('data_subject_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('request_type', 40)->index();
                $table->string('status', 40)->default('pending')->index();
                $table->text('details')->nullable();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('processed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'request_type', 'status'], 'idx_dsr_user_type_status');
            });
        }

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_ip', 45)->nullable();
                $table->text('actor_user_agent')->nullable();
                $table->string('action', 120)->index();
                $table->string('severity', 32)->default('info');
                $table->string('target_type', 120)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['target_type', 'target_id'], 'idx_admin_audit_target');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('user_consents_log');
    }
};
