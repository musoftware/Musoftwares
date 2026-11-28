<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The estimator's view of a quoted program (pages, modules, total), which the price never goes below, and the end of
 * the 48 hours an earlier trial price is still kept after the customer was told about the new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_projects', 'price_scope')) {
                $table->json('price_scope')->nullable();
            }
            if (! Schema::hasColumn('builder_projects', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropIndex(['trial_ends_at']);
            $table->dropColumn(['price_scope', 'trial_ends_at']);
        });
    }
};
