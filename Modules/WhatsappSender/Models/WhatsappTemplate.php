<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'whatsapp_business_id',
        'name',
        'category',
        'language',
        'components',
        'status',
        'meta_template_id',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }
}
