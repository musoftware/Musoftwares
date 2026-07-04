<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_cost_transactions_user_created');
            $table->index(['project_id', 'created_at'], 'idx_cost_transactions_project_created');
        });
    }

    public function down(): void
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cost_transactions_user_created');
            $table->dropIndex('idx_cost_transactions_project_created');
        });
    }
};