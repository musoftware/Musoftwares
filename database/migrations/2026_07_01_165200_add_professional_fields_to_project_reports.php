<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_reports', function (Blueprint $table) {
            $table->string('type', 32)->default('progress')->after('title');
            $table->string('priority', 16)->default('normal')->after('type');
            $table->text('summary')->nullable()->after('priority');
            $table->date('period_start')->nullable()->after('summary');
            $table->date('period_end')->nullable()->after('period_start');
            $table->boolean('notify_client')->default(false)->after('published_at');

            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('project_reports', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'type']);
            $table->dropIndex(['project_id', 'priority']);
            $table->dropColumn([
                'type',
                'priority',
                'summary',
                'period_start',
                'period_end',
                'notify_client',
            ]);
        });
    }
};