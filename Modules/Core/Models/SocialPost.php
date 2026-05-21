<?php

namespace Modules\Core\Models;

use App\Trait\Commentable;
use App\Trait\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPost extends Model
{
    use HasFactory;
    use Likeable;
    use SoftDeletes;
    use Commentable;

    protected $fillable = [
        'user_id',
        'community_id',
        'body',
        'post_image',
        'post_video',
        'post_audio',
        'post_file',
        'post_type',
        'post_status',
        'post_privacy',
        'post_location',
        'post_latitude',
        'post_longitude',
        'post_youtube',
        'post_vimeo',
        'post_dailymotion',
        'post_facebook',
        'post_metacafe',
        'post_soundcloud',
        'post_spotify',
        'media_url', // Added for media URLs
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('post_status', 'published');
    }

    /**
     * Scope for public posts.
     */
    public function scopePublic($query)
    {
        return $query->where('post_privacy', 'public');
    }

    /**
     * Get the post's main media (image, video, or audio).
     */
    public function getMainMediaAttribute()
    {
        // Check for media URL first (new approach)
        if ($this->media_url) {
            $mediaType = $this->getMediaTypeFromUrl($this->media_url);
            return [
                'type' => $mediaType,
                'url' => $this->media_url,
                'path' => $this->media_url,
                'is_url' => true
            ];
        }

        // Fallback to old file-based media
        if ($this->post_image) {
            return [
                'type' => 'image',
                'url' => asset('storage/' . $this->post_image),
                'path' => $this->post_image,
                'is_url' => false
            ];
        }

        if ($this->post_video) {
            return [
                'type' => 'video',
                'url' => asset('storage/' . $this->post_video),
                'path' => $this->post_video,
                'is_url' => false
            ];
        }

        if ($this->post_audio) {
            return [
                'type' => 'audio',
                'url' => asset('storage/' . $this->post_audio),
                'path' => $this->post_audio,
                'is_url' => false
            ];
        }

        return null;
    }

    /**
     * Determine media type from URL.
     */
    private function getMediaTypeFromUrl($url)
    {
        // YouTube
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        }
        
        // Vimeo
        if (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }
        
        // Image extensions
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $url)) {
            return 'image';
        }
        
        // Video extensions
        if (preg_match('/\.(mp4|webm|ogg|avi|mov)$/i', $url)) {
            return 'video';
        }
        
        // Audio extensions
        if (preg_match('/\.(mp3|wav|ogg|m4a)$/i', $url)) {
            return 'audio';
        }
        
        // Default to image for other URLs
        return 'image';
    }

    /**
     * Check if post has any media.
     */
    public function hasMedia(): bool
    {
        return !is_null($this->getMainMediaAttribute());
    }

    /**
     * Get formatted created date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get YouTube embed URL.
     */
    public function getYouTubeEmbedUrl()
    {
        if (!$this->media_url) return null;
        
        $url = $this->media_url;
        
        // Handle youtu.be short URLs
        if (strpos($url, 'youtu.be/') !== false) {
            $videoId = substr($url, strpos($url, 'youtu.be/') + 9);
            if (strpos($videoId, '?') !== false) {
                $videoId = substr($videoId, 0, strpos($videoId, '?'));
            }
            return "https://www.youtube.com/embed/{$videoId}";
        }
        
        // Handle youtube.com URLs
        if (strpos($url, 'youtube.com/watch') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            if (isset($params['v'])) {
                return "https://www.youtube.com/embed/{$params['v']}";
            }
        }
        
        return $url;
    }

    /**
     * Get Vimeo embed URL.
     */
    public function getVimeoEmbedUrl()
    {
        if (!$this->media_url) return null;
        
        $url = $this->media_url;
        
        if (strpos($url, 'vimeo.com/') !== false) {
            $videoId = substr($url, strpos($url, 'vimeo.com/') + 10);
            if (strpos($videoId, '?') !== false) {
                $videoId = substr($videoId, 0, strpos($videoId, '?'));
            }
            return "https://player.vimeo.com/video/{$videoId}";
        }
        
        return $url;
    }
}
