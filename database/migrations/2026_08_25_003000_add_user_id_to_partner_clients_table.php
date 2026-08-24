<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_clients')) {
            Schema::table('partner_clients', function (Blueprint $table) {
                if (!Schema::hasColumn('partner_clients', 'user_id')) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('users')
                        ->nullOnDelete();
                    $table->index('user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partner_clients')) {
            Schema::table('partner_clients', function (Blueprint $table) {
                if (Schema::hasColumn('partner_clients', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }
    }
};
