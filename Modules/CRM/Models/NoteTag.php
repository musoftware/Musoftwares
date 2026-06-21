<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class NoteTag extends Model
{
    use SoftDeletes;


    public function note()
    {
        return $this->belongsTo(Note::class);
    }


}
