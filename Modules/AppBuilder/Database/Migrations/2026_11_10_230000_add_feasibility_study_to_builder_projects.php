<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A short feasibility study written for a serious customer once the scope is agreed; its cost is part of the price. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'study_status')) {
                $table->string('study_status', 12)->nullable()->after('spec');
                $table->longText('study_html')->nullable()->after('study_status');
                $table->decimal('study_fee', 10, 2)->default(0)->after('study_html');
            }
            if (! Schema::hasColumn('builder_projects', 'project_type')) {
                $table->string('project_type', 20)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['project_type', 'study_fee', 'study_html', 'study_status'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
