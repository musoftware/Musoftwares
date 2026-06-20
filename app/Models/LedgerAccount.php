<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    protected $guarded = [];

    public function entries()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
