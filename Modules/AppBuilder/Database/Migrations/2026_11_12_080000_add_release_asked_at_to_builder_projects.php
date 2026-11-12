<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The customer asked in the chat to put the changes live: the release goes out as soon as nothing else runs. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('builder_projects', 'release_asked_at')) {
            Schema::table('builder_projects', fn (Blueprint $table) => $table->timestamp('release_asked_at')->nullable()->after('released_at'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('builder_projects', 'release_asked_at')) {
            Schema::table('builder_projects', fn (Blueprint $table) => $table->dropColumn('release_asked_at'));
        }
    }
};
