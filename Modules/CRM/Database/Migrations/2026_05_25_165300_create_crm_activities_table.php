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
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            
            // The user who triggered the action. Nullable for system automations.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // The action event, e.g., "lead.created", "stage.changed", "campaign.sent"
            $table->string('event');
            
            // The entity this activity belongs to, e.g., Lead::class, Task::class
            $table->morphs('entity');
            
            // For auditing: Tracking exact old vs new values
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            
            // Any additional context (e.g., ip address, user agent, browser)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexing for blazing fast timeline queries
            $table->index(['workspace_id', 'entity_type', 'entity_id']);
            $table->index(['workspace_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
