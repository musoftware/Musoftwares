<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'budget']);
            $table->integer('budget_points')->default(0)->after('description');
            $table->integer('min_proposal_points')->default(0)->after('budget_points');
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'bid_amount']);
            $table->integer('proposed_budget_points')->default(0)->after('cover_letter');
            $table->integer('points_spent')->default(0)->after('proposed_budget_points');
        });

        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'amount']);
            $table->integer('contract_points')->default(0)->after('freelancer_id');
        });
    }

    public function down()
    {
        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->dropColumn('contract_points');
            $table->decimal('amount', 20, 8)->default(0);
            $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->dropColumn(['points_spent', 'proposed_budget_points']);
            $table->decimal('bid_amount', 20, 8)->default(0);
            $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
        });

        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropColumn(['min_proposal_points', 'budget_points']);
            $table->decimal('budget', 20, 8)->default(0);
            $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
        });
    }
};
