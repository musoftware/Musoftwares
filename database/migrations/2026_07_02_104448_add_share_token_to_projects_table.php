<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'share_token')) {
                $table->string('share_token', 64)->nullable()->unique()->after('hour_rate');
            }
        });

        // Populate existing projects
        DB::table('projects')->whereNull('share_token')->get()->each(function ($project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['share_token' => Str::random(32)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'share_token')) {
                $table->dropColumn('share_token');
            }
        });
    }
};
