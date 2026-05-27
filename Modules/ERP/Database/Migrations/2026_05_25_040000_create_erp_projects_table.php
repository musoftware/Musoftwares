<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create erp_projects table
        if (!Schema::hasTable('erp_projects')) {
            Schema::create('erp_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('erp_tenants')->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('erp_tenant_clients')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('draft'); // draft, active, in_progress, review, completed

                $table->decimal('budget', 15, 2)->nullable();
                $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');

                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Helper function to check if foreign key exists in MySQL
        $dropForeignKeyIfExists = function (string $tableName, string $constraintName) {
            if (DB::getDriverName() === 'sqlite') {
                return;
            }
            $exists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND CONSTRAINT_NAME = ?
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$tableName, $constraintName]);

            if (!empty($exists)) {
                Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                    $table->dropForeign($constraintName);
                });
            }
        };

        // 2. Safely create erp_support_tickets table if it doesn't exist
        if (!Schema::hasTable('erp_support_tickets')) {
            Schema::create('erp_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('erp_tenant_clients')->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('erp_projects')->nullOnDelete();

                $table->string('subject');
                $table->text('description');
                $table->string('status')->default('open'); // open, assigned, waiting, resolved, closed
                $table->string('priority')->default('medium'); // low, medium, high, urgent

                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('erp_support_tickets', 'project_id')) {
                Schema::table('erp_support_tickets', function (Blueprint $table) {
                    $table->foreignId('project_id')
                        ->nullable()
                        ->constrained('erp_projects')
                        ->nullOnDelete();
                });
            } else {
                $dropForeignKeyIfExists('erp_support_tickets', 'erp_support_tickets_project_id_foreign');
                Schema::table('erp_support_tickets', function (Blueprint $table) {
                    $table->foreign('project_id')
                        ->references('id')
                        ->on('erp_projects')
                        ->nullOnDelete();
                });
            }
        }

        // 3. Safely update erp_tasks table
        if (Schema::hasTable('erp_tasks')) {
            if (!Schema::hasColumn('erp_tasks', 'project_id')) {
                Schema::table('erp_tasks', function (Blueprint $table) {
                    $table->foreignId('project_id')
                        ->nullable()
                        ->constrained('erp_projects')
                        ->nullOnDelete();
                });
            } else {
                $dropForeignKeyIfExists('erp_tasks', 'erp_tasks_project_id_foreign');
                Schema::table('erp_tasks', function (Blueprint $table) {
                    $table->foreign('project_id')
                        ->references('id')
                        ->on('erp_projects')
                        ->nullOnDelete();
                });
            }
        }

        // 4. Safely update erp_invoices table
        if (Schema::hasTable('erp_invoices')) {
            if (!Schema::hasColumn('erp_invoices', 'project_id')) {
                Schema::table('erp_invoices', function (Blueprint $table) {
                    $table->foreignId('project_id')
                        ->nullable()
                        ->after('client_id')
                        ->constrained('erp_projects')
                        ->nullOnDelete();
                });
            } else {
                $dropForeignKeyIfExists('erp_invoices', 'erp_invoices_project_id_foreign');
                Schema::table('erp_invoices', function (Blueprint $table) {
                    $table->foreign('project_id')
                        ->references('id')
                        ->on('erp_projects')
                        ->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dropForeignKeyIfExists = function (string $tableName, string $constraintName) {
            if (DB::getDriverName() === 'sqlite') {
                return;
            }
            $exists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND CONSTRAINT_NAME = ?
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$tableName, $constraintName]);

            if (!empty($exists)) {
                Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                    $table->dropForeign($constraintName);
                });
            }
        };

        // Restore foreign keys pointing to old projects table
        if (Schema::hasTable('erp_invoices') && Schema::hasColumn('erp_invoices', 'project_id')) {
            $dropForeignKeyIfExists('erp_invoices', 'erp_invoices_project_id_foreign');
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('erp_tasks') && Schema::hasColumn('erp_tasks', 'project_id')) {
            $dropForeignKeyIfExists('erp_tasks', 'erp_tasks_project_id_foreign');
            Schema::table('erp_tasks', function (Blueprint $table) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('erp_support_tickets')) {
            $dropForeignKeyIfExists('erp_support_tickets', 'erp_support_tickets_project_id_foreign');
            Schema::table('erp_support_tickets', function (Blueprint $table) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->nullOnDelete();
            });
        }

        Schema::dropIfExists('erp_projects');
    }
};
