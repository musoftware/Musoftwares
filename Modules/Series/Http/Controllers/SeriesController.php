<?php

namespace Modules\Series\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Series\Models\SeriesPlaylist;
use Modules\Series\Models\SeriesVideo;
use Modules\Series\Models\SeriesVideoProgress;
use Modules\Series\Services\YouTubeSyncService;

class SeriesController extends Controller
{
    protected $syncService;

    public function __construct(YouTubeSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Display the list of imported playlists/series.
     */
    public function index()
    {
        $userId = auth()->id();

        $playlists = SeriesPlaylist::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function (SeriesPlaylist $playlist) use ($userId) {
                $totalVideos = $playlist->videos()->count();
                
                $completedVideos = SeriesVideoProgress::where('user_id', $userId)
                    ->whereIn('series_video_id', $playlist->videos()->pluck('id'))
                    ->where('is_completed', true)
                    ->count();

                $progressPercent = $totalVideos > 0 
                    ? round(($completedVideos / $totalVideos) * 100) 
                    : 0;

                return [
                    'id' => $playlist->id,
                    'youtube_playlist_id' => $playlist->youtube_playlist_id,
                    'title' => $playlist->title,
                    'description' => $playlist->description,
                    'thumbnail' => $playlist->thumbnail,
                    'channel_title' => $playlist->channel_title,
                    'total_videos' => $totalVideos,
                    'completed_videos' => $completedVideos,
                    'progress_percent' => $progressPercent,
                    'created_at' => $playlist->created_at?->diffForHumans(),
                ];
            });

        return Inertia::render('Series/Index', [
            'playlists' => $playlists,
        ]);
    }

    /**
     * Sync a YouTube playlist.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'playlist_id' => 'required|string|max:255',
        ]);

        try {
            $playlist = $this->syncService->syncPlaylist(
                auth()->id(), 
                $request->input('playlist_id')
            );

            return redirect()->route('series.show', $playlist->id)
                ->with('success', 'تم استيراد/تحديث قائمة التشغيل بنجاح!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['playlist_id' => $e->getMessage()]);
        }
    }

    /**
     * Display the course/series learning workspace.
     */
    public function show($id)
    {
        $userId = auth()->id();
        $playlist = SeriesPlaylist::where('user_id', $userId)->findOrFail($id);

        $videos = $playlist->videos()
            ->orderBy('position', 'asc')
            ->get()
            ->map(function (SeriesVideo $video) use ($userId) {
                $progress = SeriesVideoProgress::where('user_id', $userId)
                    ->where('series_video_id', $video->id)
                    ->first();

                return [
                    'id' => $video->id,
                    'youtube_video_id' => $video->youtube_video_id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'thumbnail' => $video->thumbnail,
                    'position' => $video->position,
                    'is_completed' => $progress ? (bool) $progress->is_completed : false,
                    'notes' => $progress ? $progress->notes : '',
                ];
            });

        return Inertia::render('Series/Show', [
            'playlist' => [
                'id' => $playlist->id,
                'title' => $playlist->title,
                'description' => $playlist->description,
                'channel_title' => $playlist->channel_title,
            ],
            'videos' => $videos,
        ]);
    }

    /**
     * Save/Auto-save notes for a specific video.
     */
    public function saveNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Verify ownership through playlist relationship
        $video = SeriesVideo::whereHas('playlist', function ($q) {
            $q->where('user_id', auth()->id());
        })->findOrFail($id);

        SeriesVideoProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'series_video_id' => $video->id,
            ],
            [
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->back()->with('success', 'تم حفظ الملاحظات');
    }

    /**
     * Toggle the completion status of a video.
     */
    public function toggleComplete(Request $request, $id)
    {
        $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        // Verify ownership through playlist relationship
        $video = SeriesVideo::whereHas('playlist', function ($q) {
            $q->where('user_id', auth()->id());
        })->findOrFail($id);

        $isCompleted = $request->input('is_completed');

        SeriesVideoProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'series_video_id' => $video->id,
            ],
            [
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        return redirect()->back()->with('success', 'تم تحديث حالة التقدم');
    }
}
