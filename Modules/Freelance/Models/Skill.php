<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'freelance_skills';
    protected $fillable = ['name', 'description'];
}
