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
        if (!Schema::hasTable('blog_articles')) {
            Schema::create('blog_articles', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->unsignedBigInteger('service_id')->nullable()->index();
                $blueprint->string('title');
                $blueprint->string('slug')->unique();
                $blueprint->longText('content');
                $blueprint->text('excerpt')->nullable();
                $blueprint->string('featured_image')->nullable();
                $blueprint->string('meta_title')->nullable();
                $blueprint->text('meta_description')->nullable();
                $blueprint->string('variation_group')->nullable();
                $blueprint->integer('cycle_number')->default(1);
                $blueprint->boolean('is_published')->default(false);
                $blueprint->timestamp('published_at')->nullable();
                $blueprint->timestamps();
                $blueprint->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_articles');
    }
};
