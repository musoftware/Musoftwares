<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'plan_currency_id') && !Schema::hasColumn('plans', 'plan_currency')) {
                $table->renameColumn('plan_currency_id', 'plan_currency');
            }
            if (!Schema::hasColumn('plans', 'plan_description')) {
                $table->text('plan_description')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'plan_currency') && !Schema::hasColumn('plans', 'plan_currency_id')) {
                $table->renameColumn('plan_currency', 'plan_currency_id');
            }
            if (Schema::hasColumn('plans', 'plan_description')) {
                $table->dropColumn('plan_description');
            }
        });
    }
};
