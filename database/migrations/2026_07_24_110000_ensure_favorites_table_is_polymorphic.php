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
            Schema::table('favorites', function (Blueprint $table) {
                if (!Schema::hasColumn('favorites', 'favoritable_type')) {
                    $table->string('favoritable_type')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('favorites', 'favoritable_id')) {
                    $table->unsignedBigInteger('favoritable_id')->nullable()->after('favoritable_type');
                }
            });

            if (Schema::hasColumn('favorites', 'service_id')) {
                DB::table('favorites')
                    ->whereNotNull('service_id')
                    ->whereNull('favoritable_id')
                    ->update([
                        'favoritable_type' => 'Modules\\Marketplace\\Models\\Service',
                        'favoritable_id' => DB::raw('service_id'),
                    ]);

                Schema::table('favorites', function (Blueprint $table) {
                    try {
                        $table->dropForeign(['service_id']);
                    } catch (\Throwable $e) {
                        // ignore if index or foreign key does not exist
                    }
                    $table->dropColumn('service_id');
                });
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
        // Polymorphic migration - down non-destructive
    }
};
