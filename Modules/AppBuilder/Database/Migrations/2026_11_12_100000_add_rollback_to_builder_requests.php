<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each delivered change remembers the commits it made in the project's own git (from the commit before it to the
 * last one it made), so the owner can roll that change back from the site: the worker reverts exactly those commits.
 */
return new class extends Migration
{
    private const COLUMNS = ['git_from', 'git_to', 'rollback_asked_at', 'rolled_back_at'];

    public function up(): void
    {
        if (Schema::hasColumn('builder_requests', 'git_from')) {
            return;
        }
        Schema::table('builder_requests', function (Blueprint $table): void {
            $table->string('git_from', 64)->nullable()->after('built_in_version');
            $table->string('git_to', 64)->nullable()->after('git_from');
            $table->timestamp('rollback_asked_at')->nullable()->after('git_to');
            $table->timestamp('rolled_back_at')->nullable()->after('rollback_asked_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('builder_requests', 'git_from')) {
            Schema::table('builder_requests', fn (Blueprint $table) => $table->dropColumn(self::COLUMNS));
        }
    }
};
