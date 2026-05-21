<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteTag extends Model
{

    public function note()
    {
        return $this->belongsTo(Note::class);
    }


}
