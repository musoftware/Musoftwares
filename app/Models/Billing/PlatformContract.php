<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PlatformContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'uuid',
        'user_id',
        'project_id',
        'project_proposal_id',
        'project_name',
        'project_description',
        'reference',
        'prepared_by',
        'valid_until',
        'duration',
        'includes_hosting',
        'hosting_duration',
        'includes_support',
        'support_duration',
        'notes',
        'terms',
        'features',
        'items',
        'description',
        'total_amount',
        'currency_id',
        'start_date',
        'end_date',
        'status',
        'content',
        'payment_terms',
        'deposit_paid',
        'deposit_amount',
        'client_signature',
        'signed_at',
        'client_name',
    ];

    protected $casts = [
        'content' => 'array',
        'features' => 'array',
        'items' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'valid_until' => 'date',
        'signed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'includes_hosting' => 'boolean',
        'includes_support' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->uuid)) {
                $contract->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function currencyRelation()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function getCurrencyAttribute()
    {
        if (!$this->currencyRelation) {
            throw new \Exception("Contract {$this->id} is missing an associated currency relation.");
        }
        return $this->currencyRelation->code;
    }

    public function getTotalPriceAttribute()
    {
        return $this->total_amount;
    }

    public function getDateAttribute()
    {
        return $this->created_at;
    }

    public function getClientAttribute()
    {
        if ($this->user) return $this->user;
        
        return (object)[
            'name' => $this->client_name ?? '-',
            'email' => '-',
            'phone' => '-'
        ];
    }

    public function getLangAttribute($value)
    {
        return $value ?? ($this->content['lang'] ?? 'ar');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectProposal()
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }
}
