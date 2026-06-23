<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skill extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'freelance_skills';
    protected $fillable = ['name', 'type', 'description', 'status', 'created_by'];

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function newFactory()
    {
        return \Modules\Freelance\Database\Factories\SkillFactory::new();
    }
}
