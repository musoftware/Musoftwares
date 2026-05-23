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
            
            if (!Schema::hasColumn('prompt_generations', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_favorite');
            }
            
            if (!Schema::hasColumn('prompt_generations', 'share_title')) {
                $table->string('share_title')->nullable()->after('is_public');
            }
            
            if (!Schema::hasColumn('prompt_generations', 'share_description')) {
                $table->text('share_description')->nullable()->after('share_title');
            }
            
            if (!Schema::hasColumn('prompt_generations', 'likes_count')) {
                $table->integer('likes_count')->default(0)->after('share_description');
            }
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
