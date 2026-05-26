<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignTemplate extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_templates';

    protected $fillable = [
        'workspace_id', 'name', 'slug', 'type', 'body',
        'placeholders', 'media_url', 'media_mime_type', 'media_filename',
        'header_text', 'footer_text', 'buttons', 'quick_replies',
        'cta_url', 'cta_text',
        'wa_template_name', 'wa_template_language', 'wa_template_params',
        'category', 'is_approved', 'usage_count', 'created_by',
    ];

    protected $casts = [
        'placeholders'      => 'array',
        'buttons'           => 'array',
        'quick_replies'     => 'array',
        'wa_template_params' => 'array',
        'is_approved'       => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(WhatsAppCampaign::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Extract all placeholder keys from the body.
     */
    public function getPlaceholderKeys(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->body ?? '', $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Increment usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
