<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractVersion extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'description',
        'payment_terms',
        'content',
        'total_amount',
    ];

    protected $casts = [
        'content' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
