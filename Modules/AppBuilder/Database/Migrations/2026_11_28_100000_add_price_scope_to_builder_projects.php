<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The estimator's view of a quoted program (pages, modules, total), which the price never goes below. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('builder_projects', 'price_scope')) {
            return;
        }

        Schema::table('builder_projects', function (Blueprint $table) {
            $table->json('price_scope')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn('price_scope');
        });
    }
};
