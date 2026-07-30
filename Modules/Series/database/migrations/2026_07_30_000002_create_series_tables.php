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
        // 1. Playlists Table
        if (!Schema::hasTable('series_playlists')) {
            Schema::create('series_playlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->string('youtube_playlist_id')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->string('channel_title')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('youtube_playlist_id');
            });
        }

        // 2. Videos Table
        if (!Schema::hasTable('series_videos')) {
            Schema::create('series_videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('series_playlist_id')
                    ->constrained('series_playlists')
                    ->cascadeOnDelete();
                $table->string('youtube_video_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->integer('position')->default(0);
                $table->timestamps();

                $table->index('series_playlist_id');
                $table->index('youtube_video_id');
            });
        }

        // 3. User Video Study Progress Table
        if (!Schema::hasTable('series_video_progress')) {
            Schema::create('series_video_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignId('series_video_id')
                    ->constrained('series_videos')
                    ->cascadeOnDelete();
                $table->boolean('is_completed')->default(false);
                $table->text('notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'series_video_id']);
                $table->index('user_id');
                $table->index('series_video_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series_video_progress');
        Schema::dropIfExists('series_videos');
        Schema::dropIfExists('series_playlists');
    }
};
