<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: Create the missing projects table that was supposed to be created
 * in 2026_05_18_000002_create_erp_workflow_tables but failed partway.
 * The support_tickets and activities tables already exist; projects does not.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('budget', 15, 2)->nullable();
                $table->string('currency', 3)->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
