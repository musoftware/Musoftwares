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
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop the existing enum column
            $table->dropColumn('target_audience');
        });
        
        Schema::table('campaigns', function (Blueprint $table) {
            // Recreate the enum with the new values
            $table->enum('target_audience', ['all_users', 'specific_users', 'filtered', 'leads', 'specific_leads'])->default('all_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop the enum with new values
            $table->dropColumn('target_audience');
        });
        
        Schema::table('campaigns', function (Blueprint $table) {
            // Recreate the original enum
            $table->enum('target_audience', ['all_users', 'specific_users', 'filtered'])->default('all_users');
        });
    }
};
