<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class NoteTag extends Model
{

    public function note()
    {
        return $this->belongsTo(Note::class);
    }


}
