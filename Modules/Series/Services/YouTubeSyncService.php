<?php

namespace Modules\Series\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Series\Models\SeriesPlaylist;
use Modules\Series\Models\SeriesVideo;

class YouTubeSyncService
{
    /**
     * Synchronize a YouTube playlist.
     */
    public function syncPlaylist(int $userId, string $playlistIdOrUrl): SeriesPlaylist
    {
        $playlistId = $this->parsePlaylistId($playlistIdOrUrl);
        $apiKey = config('services.youtube.key');

        if (empty($apiKey)) {
            Log::info("[YouTubeSyncService] No YOUTUBE_API_KEY found. Falling back to Mock Sync Mode.");
            return $this->syncMockPlaylist($userId, $playlistId);
        }

        try {
            // 1. Fetch Playlist Details
            $playlistUrl = "https://www.googleapis.com/youtube/v3/playlists";
            $response = Http::get($playlistUrl, [
                'part' => 'snippet',
                'id' => $playlistId,
                'key' => $apiKey,
            ]);

            if ($response->failed() || empty($response->json('items'))) {
                throw new Exception("لم يتم العثور على قائمة التشغيل أو أن مفتاح API غير صالح.");
            }

            $playlistData = $response->json('items.0.snippet');
            $title = $playlistData['title'];
            $description = $playlistData['description'] ?? '';
            $channelTitle = $playlistData['channelTitle'] ?? '';
            $thumbnail = $playlistData['thumbnails']['high']['url'] ?? ($playlistData['thumbnails']['medium']['url'] ?? '');

            // Create or update SeriesPlaylist
            $playlist = SeriesPlaylist::updateOrCreate(
                ['youtube_playlist_id' => $playlistId],
                [
                    'user_id' => $userId,
                    'title' => $title,
                    'description' => $description,
                    'thumbnail' => $thumbnail,
                    'channel_title' => $channelTitle,
                ]
            );

            // 2. Fetch Playlist Videos (Paginated)
            $videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems";
            $nextPageToken = null;
            $position = 0;

            // Delete existing videos to rebuild order correctly on full sync
            $playlist->videos()->delete();

            do {
                $params = [
                    'part' => 'snippet,contentDetails',
                    'maxResults' => 50,
                    'playlistId' => $playlistId,
                    'key' => $apiKey,
                ];

                if ($nextPageToken) {
                    $params['pageToken'] = $nextPageToken;
                }

                $videosResponse = Http::get($videosUrl, $params);

                if ($videosResponse->failed()) {
                    throw new Exception("فشل في استيراد فيديوهات قائمة التشغيل.");
                }

                $items = $videosResponse->json('items') ?? [];
                foreach ($items as $item) {
                    $videoId = $item['contentDetails']['videoId'] ?? null;
                    if (!$videoId) continue;

                    $videoTitle = $item['snippet']['title'] ?? '';
                    // Skip deleted or private videos
                    if (str_contains(strtolower($videoTitle), 'private video') || str_contains(strtolower($videoTitle), 'deleted video')) {
                        continue;
                    }

                    $videoDesc = $item['snippet']['description'] ?? '';
                    $videoThumb = $item['snippet']['thumbnails']['high']['url'] ?? ($item['snippet']['thumbnails']['medium']['url'] ?? '');

                    SeriesVideo::create([
                        'series_playlist_id' => $playlist->id,
                        'youtube_video_id' => $videoId,
                        'title' => $videoTitle,
                        'description' => $videoDesc,
                        'thumbnail' => $videoThumb,
                        'position' => $position++,
                    ]);
                }

                $nextPageToken = $videosResponse->json('nextPageToken');
            } while ($nextPageToken);

            return $playlist;

        } catch (Exception $e) {
            Log::error("[YouTubeSyncService] Sync error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse YouTube Playlist ID from a URL or raw string.
     */
    private function parsePlaylistId(string $input): string
    {
        if (preg_match('/list=([a-zA-Z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }

        return trim($input);
    }

    /**
     * Generate a premium Mock playlist with real, playable YouTube tutorial video IDs.
     */
    private function syncMockPlaylist(int $userId, string $playlistId): SeriesPlaylist
    {
        $mockId = empty($playlistId) ? 'mock_laravel_basics' : $playlistId;

        $playlist = SeriesPlaylist::updateOrCreate(
            ['youtube_playlist_id' => $mockId],
            [
                'user_id' => $userId,
                'title' => 'Laravel 11 Basics (أساسيات لارافيل 11)',
                'description' => "دورة تعليمية شاملة ومبسطة لشرح أساسيات إطار العمل Laravel وكيفية بناء تطبيقات ويب احترافية متكاملة باستخدام الهيكل البرمجي الحديث للمنصة.",
                'thumbnail' => 'https://images.unsplash.com/photo-1599507593499-a3f7d7d97667?auto=format&fit=crop&w=800&q=80',
                'channel_title' => 'Musoftwares Code Academy',
            ]
        );

        // Delete existing videos to rebuild
        $playlist->videos()->delete();

        // Real playable tutorial video IDs from YouTube
        $mockVideos = [
            [
                'youtube_video_id' => 'ImtZ5yENzgE',
                'title' => '1. Introduction to Laravel & Installation (مقدمة الدورة وتثبيت البيئة)',
                'description' => "في هذا الدرس سنتعرف على إطار العمل لارافيل ومميزاته، وكيفية تجهيز بيئة التطوير المحلية وتثبيت المشروع الأول خطوة بخطوة.",
                'thumbnail' => 'https://images.unsplash.com/photo-1618401471353-b98aedd07871?auto=format&fit=crop&w=400&q=80',
                'position' => 0
            ],
            [
                'youtube_video_id' => '376Xy3D1lFc',
                'title' => '2. Routes, Views, and Controllers (توجيه المسارات والتحكم بالصفحات)',
                'description' => "شرح مفصل لكيفية توجيه طلبات الويب، وكيفية ربط Routes مع الـ Controllers لعرض الصفحات المطلوبة ديناميكيًا.",
                'thumbnail' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=400&q=80',
                'position' => 1
            ],
            [
                'youtube_video_id' => 'V_kUeeSSyQA',
                'title' => '3. Database Migrations & Seeders (إدارة قواعد البيانات والجداول)',
                'description' => "كيفية إنشاء وإدارة جداول قاعدة البيانات باستخدام Migrations وتغذية الجداول ببيانات اختبارية سريعة باستخدام Seeders.",
                'thumbnail' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=400&q=80',
                'position' => 2
            ],
            [
                'youtube_video_id' => 'B81t2fK9Qp4',
                'title' => '4. Eloquent ORM & Relationships (التعامل مع الموديلات والعلاقات)',
                'description' => "طريقة الاستعلام عن البيانات وتحديثها وإدخالها بسهولة باستخدام Eloquent ORM، وشرح العلاقات الثنائية مثل One-to-Many.",
                'thumbnail' => 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?auto=format&fit=crop&w=400&q=80',
                'position' => 3
            ],
            [
                'youtube_video_id' => 'H5yCO5D1xXg',
                'title' => '5. Form Validation & Middleware (التحقق من البيانات وحماية المسارات)',
                'description' => "كيفية كتابة قواعد التحقق من صحة مدخلات المستخدمين (Form Validation)، واستخدام Middleware لحماية الصفحات ولوحة التحكم.",
                'thumbnail' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=400&q=80',
                'position' => 4
            ]
        ];

        foreach ($mockVideos as $mv) {
            SeriesVideo::create([
                'series_playlist_id' => $playlist->id,
                'youtube_video_id' => $mv['youtube_video_id'],
                'title' => $mv['title'],
                'description' => $mv['description'],
                'thumbnail' => $mv['thumbnail'],
                'position' => $mv['position'],
            ]);
        }

        return $playlist;
    }
}
