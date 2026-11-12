<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How many times a crashed build was sent back to the worker by itself since its last delivery. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_projects', 'auto_retries')) {
                $table->unsignedTinyInteger('auto_retries')->default(0)->after('attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (Schema::hasColumn('builder_projects', 'auto_retries')) {
                $table->dropColumn('auto_retries');
            }
        });
    }
};
