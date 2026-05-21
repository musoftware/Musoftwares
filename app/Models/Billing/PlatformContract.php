<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'platform_contracts';

    protected $fillable = [
        'user_id',
        'project_id',
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
        'content',
        'description',
        'total_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
