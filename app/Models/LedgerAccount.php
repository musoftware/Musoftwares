<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function entries()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
