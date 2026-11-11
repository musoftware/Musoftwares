<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The ready Musoftware program a project starts from (a slug in Resources/starters.json), so the build copies it first. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->string('starter', 60)->nullable()->after('project_type');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn('starter');
        });
    }
};
