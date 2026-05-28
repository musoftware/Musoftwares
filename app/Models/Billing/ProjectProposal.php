<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Currency;

class ProjectProposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_proposals';

    protected $fillable = [
        'uuid',
        'user_id',
        'client_name',
        'client_email',
        'project_name',
        'requirements',
        'ai_estimate',
        'total_amount',
        'currency_id',
        'status',
    ];

    protected $casts = [
        'ai_estimate' => 'array',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proposal) {
            if (empty($proposal->uuid)) {
                $proposal->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
