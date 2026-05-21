<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'platform_ledger_entries';

    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'description',
        'amount',
        'currency',
        'type',
        'is_recurring',
        'recurrence_interval',
        'transaction_date',
        'next_due_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'transaction_date' => 'datetime',
        'next_due_date' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(LedgerCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
