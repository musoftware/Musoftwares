<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_links', function (Blueprint $table) {
            if (!Schema::hasColumn('shortlink_links', 'title')) {
                $table->string('title')->nullable()->after('label');
            }
            if (!Schema::hasColumn('shortlink_links', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('shortlink_links', 'image_url')) {
                $table->text('image_url')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_links', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'image_url']);
        });
    }
};
