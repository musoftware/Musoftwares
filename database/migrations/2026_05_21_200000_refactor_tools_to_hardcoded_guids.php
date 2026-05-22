<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if (Schema::hasTable('tool_subscriptions')) {
            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->index('user_id', 'temp_user_id_index');
                });
            } catch (\Exception $e) {}

            if (!$isSqlite) {
                try {
                    Schema::table('tool_subscriptions', function (Blueprint $table) {
                        $table->dropForeign('tool_subscriptions_tool_id_foreign');
                    });
                } catch (\Exception $e) {}

                try {
                    Schema::table('tool_subscriptions', function (Blueprint $table) {
                        $table->dropForeign('tool_subscriptions_tool_pricing_plan_id_foreign');
                    });
                } catch (\Exception $e) {}
            }

            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->dropIndex(['user_id', 'tool_id']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tool_licenses')) {
            try {
                Schema::table('tool_licenses', function (Blueprint $table) {
                    $table->index('user_id', 'temp_user_id_license_index');
                });
            } catch (\Exception $e) {}

            if (!$isSqlite) {
                try {
                    Schema::table('tool_licenses', function (Blueprint $table) {
                        $table->dropForeign(['tool_id']);
                    });
                } catch (\Exception $e) {}
            }

            try {
                Schema::table('tool_licenses', function (Blueprint $table) {
                    $table->dropIndex(['user_id', 'tool_id']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tool_downloads')) {
            try {
                Schema::table('tool_downloads', function (Blueprint $table) {
                    $table->index('user_id', 'temp_user_id_download_index');
                });
            } catch (\Exception $e) {}

            if (!$isSqlite) {
                try {
                    Schema::table('tool_downloads', function (Blueprint $table) {
                        $table->dropForeign(['tool_id']);
                    });
                } catch (\Exception $e) {}

                try {
                    Schema::table('tool_downloads', function (Blueprint $table) {
                        $table->dropForeign(['tool_version_id']);
                    });
                } catch (\Exception $e) {}
            }

            try {
                Schema::table('tool_downloads', function (Blueprint $table) {
                    $table->dropIndex(['user_id', 'tool_id']);
                });
            } catch (\Exception $e) {}
        }

        // Drop the tool tables
        Schema::dropIfExists('tool_downloads');
        Schema::dropIfExists('tool_screenshots');
        Schema::dropIfExists('tool_versions');
        Schema::dropIfExists('tool_pricing_plans');
        Schema::dropIfExists('tools');

        // Add tool_guid columns
        if (Schema::hasTable('tool_subscriptions')) {
            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->string('tool_guid')->after('user_id')->nullable();
                    $table->string('plan_guid')->after('tool_guid')->nullable();
                    $table->index(['user_id', 'tool_guid']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tool_licenses')) {
            try {
                Schema::table('tool_licenses', function (Blueprint $table) {
                    $table->string('tool_guid')->after('user_id')->nullable();
                    $table->index(['user_id', 'tool_guid']);
                });
            } catch (\Exception $e) {}
        }

        // Drop old integer columns
        if (Schema::hasTable('tool_subscriptions')) {
            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->dropIndex(['tool_id']);
                });
            } catch (\Exception $e) {}

            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->dropIndex(['tool_pricing_plan_id']);
                });
            } catch (\Exception $e) {}

            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->dropColumn('tool_id');
                });
            } catch (\Exception $e) {}

            try {
                Schema::table('tool_subscriptions', function (Blueprint $table) {
                    $table->dropColumn('tool_pricing_plan_id');
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tool_licenses')) {
            try {
                Schema::table('tool_licenses', function (Blueprint $table) {
                    $table->dropColumn('tool_id');
                });
            } catch (\Exception $e) {}
        }
    }

    public function down(): void
    {
        // Reverting this migration is complex as we dropped the tables and data.
        // It's not practically reversible without a database backup.
    }
};
