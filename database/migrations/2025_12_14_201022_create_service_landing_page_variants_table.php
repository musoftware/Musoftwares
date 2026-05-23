<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration is DEPRECATED - variants are stored in the main service_landing_pages table
     * using parent_variant_id to establish relationships.
     * 
     * Keeping this file for reference but it will not create a table.
     */
    public function up(): void
    {
        // This table is not needed - variants are stored in service_landing_pages
        // with parent_variant_id foreign key
        
        // If you need to migrate from this structure to the main table structure,
        // create a new migration to move data
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to drop
    }
};
