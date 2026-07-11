<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_comments', function (Blueprint $table) {
            // Drop the existing NOT NULL foreign key on author_id so guests can comment.
            $table->dropForeign(['author_id']);
        });

        Schema::table('project_comments', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->change();
        });

        Schema::table('project_comments', function (Blueprint $table) {
            $table->foreign('author_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->string('guest_name', 120)->nullable()->after('author_id');
            $table->string('guest_email', 190)->nullable()->after('guest_name');

            $table->softDeletes();

            $table->index('guest_email');
        });
    }

    public function down(): void
    {
        Schema::table('project_comments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['guest_email']);
            $table->dropColumn(['guest_name', 'guest_email']);
        });

        Schema::table('project_comments', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });

        Schema::table('project_comments', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable(false)->change();
        });

        Schema::table('project_comments', function (Blueprint $table) {
            $table->foreign('author_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
