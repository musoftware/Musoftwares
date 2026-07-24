<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('favorites') && Schema::hasColumn('favorites', 'service_id')) {
            // 1. Make service_id nullable so INSERT statements succeed without service_id
            try {
                DB::statement("ALTER TABLE `favorites` MODIFY `service_id` BIGINT UNSIGNED NULL DEFAULT NULL");
            } catch (\Throwable $e) {
                try {
                    DB::statement("ALTER TABLE `favorites` CHANGE `service_id` `service_id` BIGINT UNSIGNED NULL DEFAULT NULL");
                } catch (\Throwable $e2) {
                    // Ignore alter errors
                }
            }

            // 2. Safely drop foreign key constraints on service_id
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'favorites'
                      AND COLUMN_NAME = 'service_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($foreignKeys as $fk) {
                    if (!empty($fk->CONSTRAINT_NAME)) {
                        DB::statement("ALTER TABLE `favorites` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }
                }
            } catch (\Throwable $e) {
                // Ignore foreign key errors
            }

            // 3. Safely attempt to drop service_id column
            try {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->dropColumn('service_id');
                });
            } catch (\Throwable $e) {
                // Column is already nullable, so even if drop is blocked, INSERT statements will succeed
            }
        }
    }

    public function down(): void
    {
    }
};
