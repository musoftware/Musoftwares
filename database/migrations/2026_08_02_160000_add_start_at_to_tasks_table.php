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
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'start_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('start_at')->nullable()->after('due_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'start_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('start_at');
            });
        }
    }
};
