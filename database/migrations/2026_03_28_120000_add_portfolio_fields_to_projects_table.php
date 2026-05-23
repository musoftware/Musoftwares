<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public landing portfolio metadata on client work projects (react-home / portfolio section).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('show_on_landing_portfolio')->default(false);
            $table->string('portfolio_category', 32)->nullable();
            $table->string('portfolio_title')->nullable();
            $table->text('portfolio_description')->nullable();
            $table->json('portfolio_tech')->nullable();
            $table->string('portfolio_image', 512)->nullable();
            $table->json('portfolio_gallery')->nullable();
            $table->string('portfolio_live_url', 512)->nullable();
            $table->string('portfolio_github_url', 512)->nullable();
            $table->unsignedInteger('portfolio_sort_order')->default(0);

            $table->index(
                ['show_on_landing_portfolio', 'portfolio_sort_order'],
                'projects_landing_portfolio_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_landing_portfolio_idx');
            $table->dropColumn([
                'show_on_landing_portfolio',
                'portfolio_category',
                'portfolio_title',
                'portfolio_description',
                'portfolio_tech',
                'portfolio_image',
                'portfolio_gallery',
                'portfolio_live_url',
                'portfolio_github_url',
                'portfolio_sort_order',
            ]);
        });
    }
};
