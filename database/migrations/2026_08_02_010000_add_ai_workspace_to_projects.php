<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_understanding_pct')->default(0)->after('ai_enabled');
            $table->json('ai_summary')->nullable()->after('ai_understanding_pct');
            $table->json('ai_questions')->nullable()->after('ai_summary');
            $table->json('ai_actions_log')->nullable()->after('ai_questions');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['ai_understanding_pct', 'ai_summary', 'ai_questions', 'ai_actions_log']);
        });
    }
};
