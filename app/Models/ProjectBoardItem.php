<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoardItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'pos_x' => 'integer',
        'pos_y' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }
}
