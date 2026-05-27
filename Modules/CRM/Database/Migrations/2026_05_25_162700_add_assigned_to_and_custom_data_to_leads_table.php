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
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'assignable_type')) {
                $table->nullableMorphs('assignable'); // Creates assignable_type and assignable_id
            }
            if (!Schema::hasColumn('leads', 'custom_data')) {
                $table->json('custom_data')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropMorphs('assignable');
            $table->dropColumn('custom_data');
        });
    }
};
