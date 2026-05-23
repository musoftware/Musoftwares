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
        Schema::table('prompt_generations', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_favorite');
            $table->string('share_title')->nullable()->after('is_public');
            $table->text('share_description')->nullable()->after('share_title');
            $table->integer('likes_count')->default(0)->after('share_description');
            
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_generations', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'share_title', 'share_description', 'likes_count']);
        });
    }
};
