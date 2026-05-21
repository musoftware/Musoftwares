<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerCategory extends Model
{
    use HasFactory;

    protected $table = 'platform_ledger_categories';

    protected $fillable = [
        'name',
        'type',
        'color',
    ];

    public function entries()
    {
        return $this->hasMany(PlatformLedgerEntry::class, 'category_id');
    }
}
