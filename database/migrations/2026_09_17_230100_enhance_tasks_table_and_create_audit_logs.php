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
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'billing_type')) {
                $table->string('billing_type', 30)->default('billable')->after('task_name');
            }
            if (! Schema::hasColumn('tasks', 'billing_status')) {
                $table->string('billing_status', 30)->default('open')->after('billing_type');
            }
            if (! Schema::hasColumn('tasks', 'pending_reason')) {
                $table->string('pending_reason', 60)->nullable()->after('billing_status');
            }
            if (! Schema::hasColumn('tasks', 'ignore_reason')) {
                $table->string('ignore_reason', 100)->nullable()->after('pending_reason');
            }
            if (! Schema::hasColumn('tasks', 'ignore_notes')) {
                $table->text('ignore_notes')->nullable()->after('ignore_reason');
            }
            if (! Schema::hasColumn('tasks', 'ignored_by')) {
                $table->foreignId('ignored_by')->nullable()->after('ignore_notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'ignored_at')) {
                $table->timestamp('ignored_at')->nullable()->after('ignored_by');
            }
            if (! Schema::hasColumn('tasks', 'sla_hours')) {
                $table->unsignedInteger('sla_hours')->nullable()->after('priority');
            }
            if (! Schema::hasColumn('tasks', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('sla_hours');
            }
        });

        if (! Schema::hasTable('task_audit_logs')) {
            Schema::create('task_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
                $table->string('action', 60); // status_change, billing_type_change, ignored, restored, priority_change
                $table->string('old_value', 255)->nullable();
                $table->string('new_value', 255)->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['task_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_audit_logs');

        Schema::table('tasks', function (Blueprint $table) {
            $columnsToDrop = [
                'sla_due_at',
                'sla_hours',
                'ignored_at',
                'ignored_by',
                'ignore_notes',
                'ignore_reason',
                'pending_reason',
                'billing_status',
                'billing_type',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    if ($col === 'ignored_by') {
                        $table->dropForeign(['ignored_by']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
