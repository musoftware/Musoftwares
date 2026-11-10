<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->timestamp('angazny_drive_checked_at')->nullable()->index();
            $table->timestamp('content_rewritten_at')->nullable()->index();
            $table->string('original_name')->nullable();
            $table->text('original_description')->nullable();
            $table->json('color_images')->nullable();
            $table->timestamp('images_arranged_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->dropIndex(['angazny_drive_checked_at']);
            $table->dropIndex(['content_rewritten_at']);
            $table->dropIndex(['images_arranged_at']);
            $table->dropColumn([
                'angazny_drive_checked_at', 'content_rewritten_at', 'original_name', 'original_description',
                'color_images', 'images_arranged_at',
            ]);
        });
    }
};
