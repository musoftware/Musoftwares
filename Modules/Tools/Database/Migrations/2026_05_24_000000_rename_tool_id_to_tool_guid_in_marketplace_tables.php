<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('tool_subscriptions', 'tool_id')) {
                // Drop the foreign key that depends on this index first
                $table->dropForeign(['user_id']);
                // Drop the index before renaming
                $table->dropIndex(['user_id', 'tool_id']);
                $table->renameColumn('tool_id', 'tool_guid');
            }
            if (Schema::hasColumn('tool_subscriptions', 'tool_pricing_plan_id')) {
                $table->renameColumn('tool_pricing_plan_id', 'plan_guid');
            }
        });

        // Add the index back with the new column name
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('tool_subscriptions', 'tool_guid')) {
                $table->index(['user_id', 'tool_guid']);
                // Restore the foreign key now that we have an index again
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('tool_licenses', 'tool_id')) {
                $table->dropForeign(['user_id']);
                $table->dropIndex(['user_id', 'tool_id']);
                $table->dropColumn('tool_id');
            }
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            if (!Schema::hasColumn('tool_licenses', 'tool_guid')) {
                $table->string('tool_guid')->nullable()->after('user_id');
                $table->index(['user_id', 'tool_guid']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tool_guid']);
            $table->renameColumn('tool_guid', 'tool_id');
            $table->renameColumn('plan_guid', 'tool_pricing_plan_id');
            $table->index(['user_id', 'tool_id']);
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tool_guid']);
            $table->dropColumn('tool_guid');
            $table->unsignedBigInteger('tool_id')->nullable()->after('user_id');
            $table->index(['user_id', 'tool_id']);
        });
    }
};
