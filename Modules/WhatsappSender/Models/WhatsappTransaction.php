<?php

namespace Modules\WhatsappSender\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_business_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
