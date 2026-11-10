<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What each delivered build really cost next to what was estimated, so prices learn from real work per project
 * type. A job's cost adds up over its failed tries until it is delivered (builder_projects.job_cost_usd).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('builder_build_costs')) {
            Schema::create('builder_build_costs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
                $table->string('project_type', 30);
                $table->string('model', 60);
                $table->unsignedInteger('estimated_tokens');
                $table->decimal('cost_usd', 10, 4);
                // real cost / (estimated tokens x the model's output price): the work factor this build really had
                $table->decimal('work_factor', 8, 3);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['project_type', 'created_at']);
            });
        }

        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'job_cost_usd')) {
                $table->decimal('job_cost_usd', 10, 4)->default(0)->after('build_cost_usd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'job_cost_usd')) {
                $table->dropColumn('job_cost_usd');
            }
        });
        Schema::dropIfExists('builder_build_costs');
    }
};
