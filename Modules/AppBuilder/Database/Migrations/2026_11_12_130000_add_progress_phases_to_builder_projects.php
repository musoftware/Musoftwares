<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The phases of a big build, in the customer's words, shown under the progress bar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_projects', 'progress_phases')) {
                $table->json('progress_phases')->nullable()->after('progress_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (Schema::hasColumn('builder_projects', 'progress_phases')) {
                $table->dropColumn('progress_phases');
            }
        });
    }
};
