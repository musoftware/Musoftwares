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
        Schema::dropIfExists('crm_user_branches');
        Schema::dropIfExists('crm_branches');

        Schema::create('crm_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            
            // Materialized Path Architecture
            $table->foreignId('parent_id')->nullable()->constrained('crm_branches')->nullOnDelete();
            $table->string('path')->index(); // E.g., "1/4/9/"
            $table->integer('level')->default(0); // Root = 0
            
            $table->string('name');
            $table->string('status')->default('active');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_user_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('crm_branches')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('crm_roles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_user_branches');
        Schema::dropIfExists('crm_branches');
    }
};
