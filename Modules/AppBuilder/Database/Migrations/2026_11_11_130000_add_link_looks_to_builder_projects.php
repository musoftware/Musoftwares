<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The chat waits while the worker opens a link the customer shared in a real browser. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'awaiting_look')) {
                $table->boolean('awaiting_look')->default(false)->after('awaiting_answer');
            }
            if (! Schema::hasColumn('builder_projects', 'look_started_at')) {
                $table->timestamp('look_started_at')->nullable()->after('awaiting_look');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['look_started_at', 'awaiting_look'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
