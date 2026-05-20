<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class WaContact extends Model
{
    protected $table = 'wa_contacts';

    protected $fillable = [
        'user_id', 'phone', 'name', 'company', 'city',
        'timezone', 'language', 'lead_stage', 'engagement_score',
        'reply_count', 'tags', 'custom_fields', 'last_replied_at',
    ];

    protected $casts = [
        'tags'           => 'array',
        'custom_fields'  => 'array',
        'last_replied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(WaCampaign::class, 'wa_campaign_contacts')
            ->withPivot('status', 'account_id', 'sent_at');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WaConversation::class, 'phone', 'phone');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'phone', 'phone');
    }
}

// ── WaConversation ────────────────────────────────────────────────────────────

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WaConversation extends Model
{
    protected $table = 'wa_conversations';

    protected $fillable = [
        'user_id', 'contact_id', 'phone', 'account_id',
        'status', 'label', 'assigned_to', 'unread_count',
        'last_message', 'last_direction', 'ai_enabled', 'ai_summary', 'last_msg_at',
    ];

    protected $casts = [
        'ai_enabled' => 'boolean',
        'last_msg_at' => 'datetime',
    ];

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WaContact::class, 'contact_id');
    }

    public function assignedUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

// ── WaMessage ─────────────────────────────────────────────────────────────────

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    protected $table = 'wa_messages';

    protected $fillable = [
        'user_id', 'conversation_id', 'campaign_id', 'phone', 'account_id',
        'direction', 'content', 'media_url', 'status', 'ai_generated',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'ai_generated' => 'boolean',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];
}

// ── WaWorkflow ────────────────────────────────────────────────────────────────

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class WaWorkflow extends Model
{
    protected $table = 'wa_workflows';

    protected $fillable = [
        'user_id', 'name', 'status', 'trigger_type',
        'trigger_config', 'nodes', 'edges',
        'active_contacts', 'completed_contacts',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'nodes'          => 'array',
        'edges'          => 'array',
    ];
}

// ── WaQualityEvent ────────────────────────────────────────────────────────────

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class WaQualityEvent extends Model
{
    public $timestamps = false;
    protected $table   = 'wa_quality_events';

    protected $fillable = ['user_id', 'account_id', 'event_type', 'payload', 'occurred_at'];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];
}
