<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeepLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'target_url',
        'app_type', // whatsapp, youtube, generic, etc.
        'meta',     // JSON field for extra config
        'show_open_app',
        'show_copy',
        'show_chrome',
        'show_firefox',
        'show_default_browser',
        'copyright',
    ];

    protected $casts = [
        'meta' => 'array',
        'show_open_app' => 'boolean',
        'show_copy' => 'boolean',
        'show_chrome' => 'boolean',
        'show_firefox' => 'boolean',
        'show_default_browser' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCopyrightAttribute()
    {
        return $this->meta['copyright'] ?? null;
    }

    public function setCopyrightAttribute($value)
    {
        $meta = $this->meta ?? [];
        $meta['copyright'] = $value;
        $this->meta = $meta;
    }
}
