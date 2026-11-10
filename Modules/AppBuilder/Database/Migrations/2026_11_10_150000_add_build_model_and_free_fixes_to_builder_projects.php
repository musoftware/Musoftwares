<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each quote picks its build model, and a fix for a bug in a delivered program can be free. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'build_model')) {
                $table->string('build_model', 60)->nullable()->after('budget_usd');
            }
            if (! Schema::hasColumn('builder_projects', 'free_fix')) {
                $table->boolean('free_fix')->default(false)->after('build_model');
            }
            if (! Schema::hasColumn('builder_projects', 'free_fixes_used')) {
                $table->unsignedTinyInteger('free_fixes_used')->default(0)->after('free_fix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['free_fixes_used', 'free_fix', 'build_model'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
