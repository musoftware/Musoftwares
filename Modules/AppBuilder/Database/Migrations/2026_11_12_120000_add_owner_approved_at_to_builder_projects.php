<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A new project's first build waits for the owner's approval of its brief, like every request does. Projects that
 * were already built, or are being built, were agreed before this rule; only a first build still to come waits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('builder_projects', 'owner_approved_at')) {
            Schema::table('builder_projects', fn (Blueprint $table) => $table->timestamp('owner_approved_at')->nullable()->after('paid_at'));
        }
        DB::table('builder_projects')->whereNull('owner_approved_at')
            ->where(fn ($started) => $started->where('version', '>', 0)->orWhereIn('status', ['building', 'ready', 'failed']))
            ->update(['owner_approved_at' => DB::raw('COALESCE(paid_at, created_at)')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('builder_projects', 'owner_approved_at')) {
            Schema::table('builder_projects', fn (Blueprint $table) => $table->dropColumn('owner_approved_at'));
        }
    }
};
