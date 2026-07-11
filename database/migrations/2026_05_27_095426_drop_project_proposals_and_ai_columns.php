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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('project_proposal_milestones');
        Schema::dropIfExists('project_proposals');
        Schema::enableForeignKeyConstraints();

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'default_ai_model')) {
                $table->dropColumn('default_ai_model');
            }
            if (Schema::hasColumn('users', 'ai_models')) {
                $table->dropColumn('ai_models');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
