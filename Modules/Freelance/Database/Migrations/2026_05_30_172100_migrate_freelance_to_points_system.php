<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the points-system fields to freelance tables WITHOUT touching fiat columns.
 *
 * Final schema:
 *  freelance_jobs:      budget + currency_id (fiat) | min_proposal_points (points)
 *  freelance_proposals: bid_amount + currency_id (fiat) | proposed_budget_points + points_spent (points)
 *  freelance_contracts: amount + currency_id (fiat) | contract_points (points)
 *
 * NOTE: budget_points does NOT exist — the job budget is always fiat (budget + currency_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── freelance_jobs ──────────────────────────────────────────────
        Schema::table('freelance_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('freelance_jobs', 'min_proposal_points')) {
                $table->integer('min_proposal_points')->default(0)->after('currency_id');
            }
        });

        // ── freelance_proposals ─────────────────────────────────────────
        Schema::table('freelance_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('freelance_proposals', 'proposed_budget_points')) {
                $table->integer('proposed_budget_points')->default(0)->after('cover_letter');
            }
            if (!Schema::hasColumn('freelance_proposals', 'points_spent')) {
                $table->integer('points_spent')->default(0)->after('proposed_budget_points');
            }
        });

        // ── freelance_contracts ─────────────────────────────────────────
        Schema::table('freelance_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('freelance_contracts', 'contract_points')) {
                $table->integer('contract_points')->default(0)->after('freelancer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->dropColumn('contract_points');
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->dropColumn(['points_spent', 'proposed_budget_points']);
        });

        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropColumn('min_proposal_points');
        });
    }
};
