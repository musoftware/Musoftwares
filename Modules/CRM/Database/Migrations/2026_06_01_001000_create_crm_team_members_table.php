<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_team_members')) {
            Schema::create('crm_team_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('role')->default('member');
                $table->string('status')->default('active'); // active, suspended
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();

                $table->unique(['workspace_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_team_members');
    }
};
