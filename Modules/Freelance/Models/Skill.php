<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skill extends Model
{
    use HasFactory;
    protected $table = 'freelance_skills';
    protected $fillable = ['name', 'description', 'status', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function newFactory()
    {
        return \Modules\Freelance\Database\Factories\SkillFactory::new();
    }
}
