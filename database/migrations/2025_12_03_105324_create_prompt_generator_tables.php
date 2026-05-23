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
        // جدول قوالب البروموتات الجاهزة
        if (!Schema::hasTable('prompt_templates')) {
            Schema::create('prompt_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name_en');
                $table->string('name_ar');
                $table->text('description_en')->nullable();
                $table->text('description_ar')->nullable();
                $table->enum('category', [
                    'design', 'coding', 'writing', 'business', 'education', 'marketing', 'other'
                ]);
                $table->enum('subcategory', [
                    // Design
                    '3d', 'flat', 'realistic', 'anime', 'minimal', 'branding',
                    // Coding
                    'generate_code', 'debugging', 'refactoring', 'api', 'database',
                    // Writing
                    'article', 'ad', 'video_script', 'email', 'product_description',
                    // Business
                    'pitch_deck', 'business_plan', 'swot', 'market_analysis', 'idea_generation'
                ])->nullable();
                $table->text('template_structure')->comment('JSON structure for the template');
                $table->text('example_output')->nullable();
                $table->enum('tone', ['marketing', 'formal', 'educational', 'casual'])->nullable();
                $table->enum('length', ['short', 'medium', 'long'])->nullable();
                $table->json('tags')->nullable();
                $table->integer('usage_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('category');
                $table->index('subcategory');
                $table->index(['category', 'subcategory']);
            });
        }

        // جدول البروموتات المولدة
        if (!Schema::hasTable('prompt_generations')) {
            Schema::create('prompt_generations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('template_id')->nullable()->constrained('prompt_templates')->onDelete('set null');
                $table->enum('generation_type', [
                    'from_template', 'from_idea', 'enhance', 'reverse_image', 'analyze'
                ]);
                $table->enum('category', [
                    'design', 'coding', 'writing', 'business', 'education', 'marketing', 'other'
                ]);
                $table->text('user_input')->nullable();
                $table->text('generated_prompt');
                $table->json('parameters')->nullable()->comment('Style, tone, length, etc.');
                $table->text('image_path')->nullable()->comment('For reverse prompt feature');
                $table->text('analysis_notes')->nullable()->comment('For analyze feature');
                $table->json('tags')->nullable();
                $table->string('language', 10)->default('en');
                $table->boolean('is_favorite')->default(false);
                $table->timestamps();

                $table->index('user_id');
                $table->index('category');
                $table->index('generation_type');
                $table->index('is_favorite');
            });
        }

        // جدول المفضلات (اختياري - يمكن استخدام is_favorite في prompt_generations)
        if (!Schema::hasTable('prompt_favorites')) {
            Schema::create('prompt_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('generation_id')->constrained('prompt_generations')->onDelete('cascade');
                $table->string('custom_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'generation_id']);
            });
        }

        // جدول السجل والتحسينات المتعاقبة
        if (!Schema::hasTable('prompt_generation_iterations')) {
            Schema::create('prompt_generation_iterations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('parent_generation_id')->constrained('prompt_generations')->onDelete('cascade');
                $table->text('iteration_input')->nullable();
                $table->text('iteration_output');
                $table->integer('iteration_number')->default(1);
                $table->timestamps();

                $table->index(['parent_generation_id', 'iteration_number'], 'pgi_parent_iter_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_generation_iterations');
        Schema::dropIfExists('prompt_favorites');
        Schema::dropIfExists('prompt_generations');
        Schema::dropIfExists('prompt_templates');
    }
};
