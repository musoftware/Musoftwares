<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            // Ensure user_id has an index so the user_id foreign key constraint doesn't fail
            // when we drop the compound user_id_tool_id_index.
            $table->index('user_id', 'temp_user_id_index');
            
            // Note: tool_subscriptions does NOT have foreign keys on tool_id or tool_pricing_plan_id, only indexes
            $table->dropIndex('tool_subscriptions_tool_id_foreign'); 
            $table->dropIndex('tool_subscriptions_tool_pricing_plan_id_foreign'); 
            $table->dropIndex(['user_id', 'tool_id']);
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->index('user_id', 'temp_user_id_license_index');
            $table->dropForeign(['tool_id']);
            $table->dropIndex(['user_id', 'tool_id']);
        });

        Schema::table('tool_downloads', function (Blueprint $table) {
            $table->index('user_id', 'temp_user_id_download_index');
            $table->dropForeign(['tool_id']);
            $table->dropForeign(['tool_version_id']);
            $table->dropIndex(['user_id', 'tool_id']);
        });

        // Drop the tool tables
        Schema::dropIfExists('tool_downloads');
        Schema::dropIfExists('tool_screenshots');
        Schema::dropIfExists('tool_versions');
        Schema::dropIfExists('tool_pricing_plans');
        Schema::dropIfExists('tools');

        // Add tool_guid columns
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            $table->string('tool_guid')->after('user_id')->nullable();
            $table->string('plan_guid')->after('tool_guid')->nullable();
            
            $table->index(['user_id', 'tool_guid']);
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->string('tool_guid')->after('user_id')->nullable();
            
            $table->index(['user_id', 'tool_guid']);
        });

        // Drop old integer columns
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            $table->dropColumn('tool_id');
            $table->dropColumn('tool_pricing_plan_id');
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->dropColumn('tool_id');
        });
    }

    public function down(): void
    {
        // Reverting this migration is complex as we dropped the tables and data.
        // It's not practically reversible without a database backup.
    }
};
