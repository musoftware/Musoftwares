<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds any columns that may be missing from free_downloads (e.g. if table was created with a minimal schema).
     */
    public function up(): void
    {
        Schema::table('free_downloads', function (Blueprint $table) {
            if (! Schema::hasColumn('free_downloads', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('free_downloads', 'programming_language')) {
                $table->string('programming_language', 64)->nullable();
            }
            if (! Schema::hasColumn('free_downloads', 'image')) {
                $table->string('image')->nullable();
            }
            if (! Schema::hasColumn('free_downloads', 'file_path')) {
                $table->string('file_path')->nullable();
            }
            if (! Schema::hasColumn('free_downloads', 'original_filename')) {
                $table->string('original_filename')->nullable();
            }
            if (! Schema::hasColumn('free_downloads', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('free_downloads', 'order_column')) {
                $table->unsignedInteger('order_column')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('free_downloads', function (Blueprint $table) {
            $columns = ['description', 'programming_language', 'image', 'file_path', 'original_filename', 'is_active', 'order_column'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('free_downloads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
