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
        Schema::table('service_landing_pages', function (Blueprint $table) {
            // A/B Testing Settings
            $table->boolean('ab_testing_enabled')->default(false)->after('google_analytics_id');
            $table->unsignedBigInteger('parent_variant_id')->nullable()->after('ab_testing_enabled');
            $table->string('variant_name')->nullable()->after('parent_variant_id'); // A, B, C, etc.
            $table->integer('traffic_split_percentage')->default(50)->after('variant_name'); // 0-100
            $table->integer('auto_winner_visits')->nullable()->after('traffic_split_percentage'); // Auto-select winner after X visits
            $table->boolean('is_winner')->default(false)->after('auto_winner_visits');

            // Sticky CTA Settings
            $table->boolean('sticky_cta_enabled')->default(false)->after('is_winner');
            $table->string('sticky_cta_text')->nullable()->after('sticky_cta_enabled');
            $table->string('sticky_cta_position')->default('bottom')->after('sticky_cta_text'); // bottom, top
            $table->boolean('sticky_cta_mobile_only')->default(true)->after('sticky_cta_position');

            // Exit Intent Settings
            $table->boolean('exit_intent_enabled')->default(false)->after('sticky_cta_mobile_only');
            $table->string('exit_intent_title')->nullable()->after('exit_intent_enabled');
            $table->text('exit_intent_message')->nullable()->after('exit_intent_title');
            $table->string('exit_intent_cta_text')->nullable()->after('exit_intent_message');
            $table->boolean('exit_intent_desktop_only')->default(true)->after('exit_intent_cta_text');

            // Time-based Popup Settings
            $table->boolean('time_based_popup_enabled')->default(false)->after('exit_intent_desktop_only');
            $table->integer('time_based_popup_delay')->default(30)->after('time_based_popup_enabled'); // seconds
            $table->string('time_based_popup_title')->nullable()->after('time_based_popup_delay');
            $table->text('time_based_popup_message')->nullable()->after('time_based_popup_title');
            $table->string('time_based_popup_cta_text')->nullable()->after('time_based_popup_message');

            // Foreign key for parent variant
            $table->foreign('parent_variant_id')->references('id')->on('service_landing_pages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_landing_pages', function (Blueprint $table) {
            $table->dropForeign(['parent_variant_id']);
            $table->dropColumn([
                'ab_testing_enabled',
                'parent_variant_id',
                'variant_name',
                'traffic_split_percentage',
                'auto_winner_visits',
                'is_winner',
                'sticky_cta_enabled',
                'sticky_cta_text',
                'sticky_cta_position',
                'sticky_cta_mobile_only',
                'exit_intent_enabled',
                'exit_intent_title',
                'exit_intent_message',
                'exit_intent_cta_text',
                'exit_intent_desktop_only',
                'time_based_popup_enabled',
                'time_based_popup_delay',
                'time_based_popup_title',
                'time_based_popup_message',
                'time_based_popup_cta_text',
            ]);
        });
    }
};
