<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserSkill extends Model
{
    protected $table = 'freelance_user_skills';
    protected $fillable = ['user_id', 'skill_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
