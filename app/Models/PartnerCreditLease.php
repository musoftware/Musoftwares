<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCreditLease extends Model
{
    use HasFactory;

    protected $table = 'partner_credit_leases';

    protected $fillable = [
        'partner_client_id',
        'lease_id',
        'granted_messages',
        'settled_messages',
        'reserved_amount',
        'final_charged_amount',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'granted_messages' => 'integer',
        'settled_messages' => 'integer',
        'reserved_amount' => 'decimal:4',
        'final_charged_amount' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PartnerClient, PartnerCreditLease>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(PartnerClient::class, 'partner_client_id');
    }
}
