<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The owner approves each request before the engineer starts it. Requests that already went past the price were
 * agreed before this rule, so they count as approved; only a request still waiting for payment waits for the owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('builder_requests', 'approved_at')) {
            Schema::table('builder_requests', fn (Blueprint $table) => $table->timestamp('approved_at')->nullable()->after('paid_at'));
        }
        DB::table('builder_requests')->whereNull('approved_at')->where('status', '!=', 'unpaid')->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('builder_requests', 'approved_at')) {
            Schema::table('builder_requests', fn (Blueprint $table) => $table->dropColumn('approved_at'));
        }
    }
};
