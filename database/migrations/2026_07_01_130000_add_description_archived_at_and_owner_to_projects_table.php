<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add project description, archived_at timestamp, and owner_id for admin projects.
     *
     * - description: free-text, max 5000 chars (admin notes + client brief).
     * - archived_at: explicit soft-archive timestamp so admins can sort/filter
     *   by "recently archived" and so the existing `archived` boolean stays in
     *   sync with the soft-archive lifecycle.
     * - owner_id: admin/staff member responsible for the project (nullable;
     *   users.id ON DELETE SET NULL so we don't cascade-delete a project when
     *   a staff user leaves).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'description')) {
                $table->text('description')->nullable()->after('project_name');
            }
            if (! Schema::hasColumn('projects', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('archived');
            }
            if (! Schema::hasColumn('projects', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('user_id');
                $table->foreign('owner_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            }

            $table->index('archived_at');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'owner_id')) {
                $table->dropForeign(['owner_id']);
                $table->dropIndex(['owner_id']);
                $table->dropColumn('owner_id');
            }
            if (Schema::hasColumn('projects', 'archived_at')) {
                $table->dropIndex(['archived_at']);
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('projects', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
