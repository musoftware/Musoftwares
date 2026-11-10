<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How much of a paid build is done, with one plain line on the last finished part, for the customer's progress bar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'progress_percent')) {
                $table->unsignedTinyInteger('progress_percent')->nullable()->after('progress_file');
            }
            if (! Schema::hasColumn('builder_projects', 'progress_note')) {
                $table->string('progress_note', 300)->nullable()->after('progress_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['progress_note', 'progress_percent'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
