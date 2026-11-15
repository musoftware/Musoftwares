<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A link to a real example of each small service, shown on the public page. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('micro_services', 'example_url')) {
            return;
        }

        Schema::table('micro_services', function (Blueprint $table) {
            $table->string('example_url', 500)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('micro_services', 'example_url')) {
            Schema::table('micro_services', fn (Blueprint $table) => $table->dropColumn('example_url'));
        }
    }
};
