<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_contact_group_id',
        'name',
        'phone',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsappContactGroup::class, 'whatsapp_contact_group_id');
    }
}
