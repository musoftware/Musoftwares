<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerUsageLog extends Model
{
    use HasFactory;

    protected $table = 'partner_usage_logs';

    protected $fillable = [
        'partner_client_id',
        'lease_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<PartnerClient, PartnerUsageLog>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(PartnerClient::class, 'partner_client_id');
    }
}
