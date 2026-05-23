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
        if (!Schema::hasTable('erp_team_members')) {
            Schema::create('erp_team_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('role')->default('member'); // member, manager
                $table->string('status')->default('active'); // active, suspended
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();

                $table->unique(['tenant_id', 'email']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_team_members');
    }
};
