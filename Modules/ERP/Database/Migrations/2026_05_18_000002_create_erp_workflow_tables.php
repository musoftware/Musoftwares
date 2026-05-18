<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add Status to tenant_clients (guard against re-run)
        if (!Schema::hasColumn('tenant_clients', 'status')) {
            Schema::table('tenant_clients', function (Blueprint $table) {
                $table->string('status')->default('lead')->after('name'); // lead, active, paying, retained, archived
            });
        }

        // 2. Projects Table
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('draft'); // draft, active, in_progress, review, completed

                $table->decimal('budget', 15, 2)->nullable();
                $table->string('currency', 3)->nullable();

                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. ERP Support Tickets Table (erp_support_tickets — distinct from core platform support_tickets)
        if (!Schema::hasTable('erp_support_tickets')) {
            Schema::create('erp_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

                $table->string('subject');
                $table->text('description');
                $table->string('status')->default('open'); // open, assigned, waiting, resolved, closed
                $table->string('priority')->default('medium'); // low, medium, high, urgent

                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 4. Activity Log (Operational History) Table
        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('tenant_clients')->cascadeOnDelete();

                $table->string('subject_type')->nullable(); // Model class
                $table->unsignedBigInteger('subject_id')->nullable(); // Model ID

                $table->string('action'); // e.g., 'invoice_paid', 'project_created'
                $table->text('description'); // e.g., 'Invoice #INV-001 was paid'

                $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('properties')->nullable();

                $table->timestamps();

                $table->index(['subject_type', 'subject_id']);
            });
        }

        // 5. Update invoices table status column
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Change enum to string to support new workflow states flexibly.
                $table->string('status')->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('erp_support_tickets');
        Schema::dropIfExists('projects');

        if (Schema::hasColumn('tenant_clients', 'status')) {
            Schema::table('tenant_clients', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
