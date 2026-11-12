<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A project the owner imports (a Google Drive zip, optionally its GitHub repo) for a customer to keep changing in the
 * chat, and how its changes are released: pushed to GitHub as a pull request and deployed, after every change, in
 * batches, or when the owner says so.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'job_kind')) {
                return;
            }
            // what the worker does with a queued project: build a change, import the project, or release changes
            $table->string('job_kind', 10)->default('build')->after('status');
            $table->text('import_url')->nullable();
            // what the project is (the owner's words plus what the worker found in its files); every change is judged against it
            $table->text('import_profile')->nullable();
            $table->string('github_repo', 200)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->string('release_mode', 10)->nullable();
            $table->unsignedSmallInteger('release_after_changes')->default(3);
            $table->unsignedSmallInteger('release_quiet_minutes')->default(60);
            $table->boolean('deploy_enabled')->default(false);
            $table->string('deploy_command', 500)->nullable();
            $table->unsignedInteger('released_version')->default(0);
            $table->timestamp('released_at')->nullable();
            $table->string('pull_request_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['pull_request_url', 'released_at', 'released_version', 'deploy_command', 'deploy_enabled', 'release_quiet_minutes', 'release_after_changes', 'release_mode', 'imported_at', 'github_repo', 'import_profile', 'import_url', 'job_kind'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
