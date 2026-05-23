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
        if (config('database.default') !== 'sqlite') {
            Schema::table('leads', function (Blueprint $table) {
                // Make email field nullable to allow phone-only leads
                $table->string('email')->nullable()->change();
                
                // Add locale field if it doesn't exist
                if (!Schema::hasColumn('leads', 'locale')) {
                    $table->string('locale')->nullable();
                }
                
                // Add phone field if it doesn't exist (in case it wasn't added by the other migration)
                if (!Schema::hasColumn('leads', 'phone')) {
                    $table->string('phone')->nullable();
                }
            });
        } else {
            // For SQLite, just add the new columns without changing existing ones
            Schema::table('leads', function (Blueprint $table) {
                // Add locale field if it doesn't exist
                if (!Schema::hasColumn('leads', 'locale')) {
                    $table->string('locale')->nullable();
                }
                
                // Add phone field if it doesn't exist (in case it wasn't added by the other migration)
                if (!Schema::hasColumn('leads', 'phone')) {
                    $table->string('phone')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('leads', function (Blueprint $table) {
                // Note: Making email required again would require checking for existing null values
                // For now, we'll leave it nullable in the down migration as well
                // $table->string('email')->nullable(false)->change();
            });
        }
    }
};
