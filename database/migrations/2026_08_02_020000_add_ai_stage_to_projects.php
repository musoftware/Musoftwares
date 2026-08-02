<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'ai_stage')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('ai_stage')->default('greeting')->after('ai_understanding_pct');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'ai_stage')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('ai_stage');
            });
        }
    }
};
