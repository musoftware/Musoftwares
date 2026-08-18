<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_products', function (Blueprint $table) {
            $table->boolean('has_free_edition')->default(false)->after('is_free');
            $table->string('free_edition_title')->nullable()->after('has_free_edition');
            $table->string('free_edition_file_path')->nullable()->after('free_edition_title');
            $table->string('free_edition_cover_path')->nullable()->after('free_edition_file_path');
            $table->unsignedInteger('free_edition_page_count')->nullable()->after('free_edition_cover_path');
            $table->unsignedBigInteger('free_edition_file_size')->nullable()->after('free_edition_page_count');
            $table->unsignedBigInteger('free_edition_download_count')->default(0)->after('free_edition_file_size');
        });

        Schema::table('digital_product_downloads', function (Blueprint $table) {
            $table->string('edition_type', 30)->default('full')->after('digital_product_id'); // 'full' or 'playbook'
        });
    }

    public function down(): void
    {
        Schema::table('digital_products', function (Blueprint $table) {
            $table->dropColumn([
                'has_free_edition',
                'free_edition_title',
                'free_edition_file_path',
                'free_edition_cover_path',
                'free_edition_page_count',
                'free_edition_file_size',
                'free_edition_download_count',
            ]);
        });

        Schema::table('digital_product_downloads', function (Blueprint $table) {
            $table->dropColumn('edition_type');
        });
    }
};
