<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TodoChecklistItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'todo_id',
        'title',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }
}
