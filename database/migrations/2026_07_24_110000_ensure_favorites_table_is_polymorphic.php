<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('favorites')) {
            if (!Schema::hasColumn('favorites', 'favoritable_type')) {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->string('favoritable_type')->nullable()->after('user_id');
                });
            }

            if (!Schema::hasColumn('favorites', 'favoritable_id')) {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->unsignedBigInteger('favoritable_id')->nullable()->after('favoritable_type');
                });
            }

            if (!Schema::hasColumn('favorites', 'deleted_at')) {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }

            if (Schema::hasColumn('favorites', 'service_id')) {
                DB::table('favorites')
                    ->whereNotNull('service_id')
                    ->whereNull('favoritable_id')
                    ->update([
                        'favoritable_type' => 'Modules\\Marketplace\\Models\\Service',
                        'favoritable_id' => DB::raw('service_id'),
                    ]);

                // Safely drop any foreign key constraints on service_id if present
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
                    // Ignore foreign key drop errors
                }

                // Safely drop column service_id
                try {
                    Schema::table('favorites', function (Blueprint $table) {
                        $table->dropColumn('service_id');
                    });
                } catch (\Throwable $e) {
                    // Ignore if column already dropped
                }
            }
        } else {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('favoritable_type');
                $table->unsignedBigInteger('favoritable_id');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
            });
        }
    }

    public function down(): void
    {
    }
};
