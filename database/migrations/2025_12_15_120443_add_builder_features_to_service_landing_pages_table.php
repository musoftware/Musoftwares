<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_landing_pages', function (Blueprint $table) {
            // Layout & Style
            $table->json('layout_config')->nullable()->after('is_active')->comment('Stores section visibility and ordering');
            $table->json('style_config')->nullable()->after('layout_config')->comment('Stores global styles like colors, fonts, radius');
            
            // Forms & Leads
            $table->json('form_config')->nullable()->after('description')->comment('Stores form fields definition as JSON');
            $table->json('lead_routing_config')->nullable()->after('form_config')->comment('Stores lead routing destinations (email, webhook, etc)');
            
            // Publishing
            $table->timestamp('published_at')->nullable()->after('lead_routing_config');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            
            // AI
            $table->integer('ai_seo_score')->nullable()->after('scheduled_at');
            $table->string('ai_persona')->nullable()->after('ai_seo_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'layout_config',
                'style_config',
                'form_config',
                'lead_routing_config',
                'published_at',
                'scheduled_at',
                'ai_seo_score',
                'ai_persona',
            ]);
        });
    }
};
