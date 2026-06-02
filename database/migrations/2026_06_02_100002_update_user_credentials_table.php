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
        // Drop the user_notes table if it was created
        Schema::dropIfExists('user_notes');

        // Add missing columns to user_credentials
        Schema::table('user_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('user_credentials', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_credentials', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('user_credentials', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::hasColumn('user_credentials', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
        });
    }
};
