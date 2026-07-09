<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'freelance_skills';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public $timestamps = true;

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'freelance_user_skills',
            'skill_id',
            'user_id'
        );
    }
}
