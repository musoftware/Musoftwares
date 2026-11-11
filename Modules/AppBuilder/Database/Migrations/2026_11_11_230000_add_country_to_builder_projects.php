<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The visitor's country (from the IP, or the browser's time zone), so the chat speaks their own dialect. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->char('country', 2)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
