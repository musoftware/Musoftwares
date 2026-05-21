<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add multi-tenant and iframe config fields to Campaigns
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('campaigns', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('campaigns', 'embed_token')) {
                    $table->uuid('embed_token')->nullable()->after('user_id')->unique();
                }
                if (!Schema::hasColumn('campaigns', 'form_title')) {
                    $table->string('form_title')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'form_description')) {
                    $table->text('form_description')->nullable();
                }
                if (!Schema::hasColumn('campaigns', 'button_text')) {
                    $table->string('button_text')->nullable();
                }
            });
        }

        // Add multi-tenant fields to Leads
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('leads', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('leads', 'source')) {
                    $table->string('source')->nullable()->after('campaign_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn(['user_id', 'embed_token', 'form_title', 'form_description', 'button_text']);
            });
        }
        
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn(['user_id', 'campaign_id', 'source']);
            });
        }
    }
};
