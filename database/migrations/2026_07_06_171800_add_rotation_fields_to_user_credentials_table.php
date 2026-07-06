<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('user_credentials', 'rotated_at')) {
                $table->timestamp('rotated_at')->nullable()->after('is_pinned');
            }
            if (!Schema::hasColumn('user_credentials', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('rotated_at');
            }
            if (!Schema::hasColumn('user_credentials', 'last_revealed_at')) {
                $table->timestamp('last_revealed_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('user_credentials', 'last_revealed_by')) {
                $table->foreignId('last_revealed_by')->nullable()->after('last_revealed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_credentials', function (Blueprint $table) {
            foreach (['last_revealed_by', 'last_revealed_at', 'expires_at', 'rotated_at'] as $col) {
                if (Schema::hasColumn('user_credentials', $col)) {
                    if (in_array($col, ['last_revealed_by'], true)) {
                        $table->dropForeign([$col]);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
