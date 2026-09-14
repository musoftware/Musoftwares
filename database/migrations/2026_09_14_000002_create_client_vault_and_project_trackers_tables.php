<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'progress_stage')) {
                $table->string('progress_stage')->default('planning')->index()->after('status');
            }
            if (! Schema::hasColumn('projects', 'progress_percentage')) {
                $table->unsignedTinyInteger('progress_percentage')->default(0)->after('progress_stage');
            }
            if (! Schema::hasColumn('projects', 'is_brief_complete')) {
                $table->boolean('is_brief_complete')->default(false)->after('progress_percentage');
            }
            if (! Schema::hasColumn('projects', 'brief_details')) {
                $table->text('brief_details')->nullable()->after('is_brief_complete');
            }
            if (! Schema::hasColumn('projects', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('brief_details');
            }
        });

        if (! Schema::hasTable('project_milestones')) {
            Schema::create('project_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('title');
                $table->string('stage')->default('planning'); // planning, development, testing, delivered
                $table->boolean('is_completed')->default(false)->index();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedSmallInteger('order_index')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'stage']);
            });
        }

        if (! Schema::hasTable('client_vault_assets')) {
            Schema::create('client_vault_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('title');
                $table->string('asset_type')->index(); // source_code, delivery_build, contract, final_invoice
                $table->string('storage_path');
                $table->string('file_mime_type')->default('application/octet-stream');
                $table->unsignedBigInteger('file_size_bytes')->default(0);
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamp('last_accessed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'asset_type']);
            });
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'priority_score')) {
                $table->unsignedInteger('priority_score')->default(0)->index()->after('priority');
            }
            if (! Schema::hasColumn('tickets', 'is_self_service')) {
                $table->boolean('is_self_service')->default(true)->after('priority_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('tickets', 'priority_score')) {
                $cols[] = 'priority_score';
            }
            if (Schema::hasColumn('tickets', 'is_self_service')) {
                $cols[] = 'is_self_service';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('client_vault_assets');
        Schema::dropIfExists('project_milestones');

        Schema::table('projects', function (Blueprint $table) {
            $cols = [];
            foreach (['progress_stage', 'progress_percentage', 'is_brief_complete', 'brief_details', 'delivered_at'] as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $cols[] = $col;
                }
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
