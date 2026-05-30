<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'freelance_skills';
    protected $fillable = ['name', 'description', 'status', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
