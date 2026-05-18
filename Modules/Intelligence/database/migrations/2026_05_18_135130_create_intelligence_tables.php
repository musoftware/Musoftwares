<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intelligence_competitors', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_tracked_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained('intelligence_competitors')->cascadeOnDelete();
            $table->string('platform'); // fb, ig, tiktok, web
            $table->string('url');
            $table->string('status')->default('active');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained('intelligence_competitors')->cascadeOnDelete();
            $table->string('ad_id')->nullable(); // external ad id
            $table->string('platform');
            $table->text('ad_copy')->nullable();
            $table->string('creative_url')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_landing_page_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_asset_id')->constrained('intelligence_tracked_assets')->cascadeOnDelete();
            $table->string('snapshot_url')->nullable();
            $table->string('html_hash')->nullable();
            $table->json('diff_summary_json')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_ugc_creators', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('handle')->nullable();
            $table->string('platform');
            $table->string('niche')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_swipe_collections', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_swipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('intelligence_swipe_collections')->cascadeOnDelete();
            $table->string('source_type'); // ad, ugc, snapshot
            $table->unsignedBigInteger('source_id');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_activities', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('competitor_id')->nullable()->constrained('intelligence_competitors')->nullOnDelete();
            $table->string('event_type');
            $table->json('data_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_activities');
        Schema::dropIfExists('intelligence_swipe_items');
        Schema::dropIfExists('intelligence_swipe_collections');
        Schema::dropIfExists('intelligence_ugc_creators');
        Schema::dropIfExists('intelligence_landing_page_snapshots');
        Schema::dropIfExists('intelligence_ads');
        Schema::dropIfExists('intelligence_tracked_assets');
        Schema::dropIfExists('intelligence_competitors');
    }
};
