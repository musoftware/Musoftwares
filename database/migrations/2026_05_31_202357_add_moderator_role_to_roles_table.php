<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deprecated: role is now seeded centrally in RolesAndPermissionsSeeder
    }

    public function down(): void
    {
        // Deprecated
    }
};
