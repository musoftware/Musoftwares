<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Seed the 'freelancer' role so users can be assigned the freelancer role
     * upon approval. Uses the existing Role::createRule pattern.
     */
    public function up(): void
    {
        Role::createRule('Freelancer', 'freelancer');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('slug', 'freelancer')->delete();
    }
};
