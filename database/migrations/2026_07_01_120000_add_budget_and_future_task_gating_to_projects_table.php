<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'budget')) {
                $table->decimal('budget', 33, 3)->default(0)->after('project_balance');
            }
            if (! Schema::hasColumn('projects', 'hide_future_tasks')) {
                $table->boolean('hide_future_tasks')->default(true)->after('budget');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'hide_future_tasks')) {
                $table->dropColumn('hide_future_tasks');
            }
            if (Schema::hasColumn('projects', 'budget')) {
                $table->dropColumn('budget');
            }
        });
    }
};
