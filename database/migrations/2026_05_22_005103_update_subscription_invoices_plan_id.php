<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the old FK to module_plans and make plan_id a generic unsignedBigInteger.
     * This allows subscription_invoices to reference either module_plans or platform_plans.
     */
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['plan_id']);

            // Make plan_id nullable (custom plans may not have a plan_id)
            $table->unsignedBigInteger('plan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('module_plans')->cascadeOnDelete();
        });
    }
};
