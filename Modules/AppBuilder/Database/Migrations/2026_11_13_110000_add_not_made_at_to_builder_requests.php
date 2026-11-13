<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the engineer reports a request as not possible, it is not delivered: it waits for the owner (who reads the
 * reason, changes it or turns it down) and is never started again on its own, also when approvals are off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_requests', 'not_made_at')) {
                $table->timestamp('not_made_at')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_requests', function (Blueprint $table) {
            if (Schema::hasColumn('builder_requests', 'not_made_at')) {
                $table->dropColumn('not_made_at');
            }
        });
    }
};
