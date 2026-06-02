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
        if (Schema::hasTable('freelance_skills')) {
            Schema::table('freelance_skills', function (Blueprint $table) {
                if (!Schema::hasColumn('freelance_skills', 'status')) {
                    $table->string('status')->default('approved'); // approved, pending, rejected
                }
                if (!Schema::hasColumn('freelance_skills', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'can_add_freelance_skills')) {
                    $table->boolean('can_add_freelance_skills')->default(true);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('freelance_skills')) {
            Schema::table('freelance_skills', function (Blueprint $table) {
                if (Schema::hasColumn('freelance_skills', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('freelance_skills', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'can_add_freelance_skills')) {
                    $table->dropColumn('can_add_freelance_skills');
                }
            });
        }
    }
};
