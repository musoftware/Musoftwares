<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access details for a project (SSH, FTP, keys), stored encrypted, and a flag for a build that is waiting
 * for an answer from the customer or the team.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('builder_project_secrets')) {
            Schema::create('builder_project_secrets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
                $table->string('name', 60);
                // encrypted with the app key by the model's cast; never shown again after it is saved
                $table->text('value');
                $table->string('source', 10)->default('customer');
                $table->timestamps();
                $table->softDeletes();
                $table->index(['project_id', 'name']);
            });
        }

        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'awaiting_answer')) {
                $table->boolean('awaiting_answer')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'awaiting_answer')) {
                $table->dropColumn('awaiting_answer');
            }
        });
        Schema::dropIfExists('builder_project_secrets');
    }
};
