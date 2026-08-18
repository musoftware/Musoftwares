<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('digital_categories')->nullOnDelete();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->unsignedBigInteger('currency_id')->default(1);
            $table->boolean('is_free')->default(false);
            $table->string('file_path');
            $table->string('cover_image_path')->nullable();
            $table->string('sample_file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->string('author_name')->nullable();
            $table->string('publisher')->nullable();
            $table->string('publication_year', 10)->nullable();
            $table->string('language', 10)->default('ar');
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_products');
    }
};
