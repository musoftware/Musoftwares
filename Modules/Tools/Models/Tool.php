<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tool extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'short_description',
        'icon', 'category', 'supported_os', 'current_version',
        'is_active', 'is_featured', 'is_free', 'download_count',
        'features', 'requirements', 'runner_component',
    ];

    protected $casts = [
        'supported_os'   => 'array',
        'features'       => 'array',
        'requirements'   => 'array',
        'is_active'      => 'boolean',
        'is_featured'    => 'boolean',
        'is_free'        => 'boolean',
        'download_count' => 'integer',
    ];

    public static array $categories = [
        'scraper'       => 'Scraper',
        'automation'    => 'Automation',
        'whatsapp'      => 'WhatsApp',
        'ocr'           => 'OCR',
        'ai'            => 'AI & Processing',
        'intelligence'  => 'Intelligence',
        'analytics'     => 'Analytics',
        'data'          => 'Data Extraction',
        'browser'       => 'Browser Tools',
        'monitoring'    => 'Monitoring',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ToolVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ToolVersion::class)->where('is_latest', true);
    }

    public function pricingPlans(): HasMany
    {
        return $this->hasMany(ToolPricingPlan::class)->orderBy('sort_order');
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(ToolScreenshot::class)->orderBy('sort_order');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ToolSubscription::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ToolLicense::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ToolDownload::class);
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset('storage/' . $this->icon) : null;
    }

    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }
}
