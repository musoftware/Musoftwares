<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class Note extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'lb_content',
        'hash',
        'slug'
    ];

    public function note_tags()
    {
        return $this->hasMany(NoteTag::class);
    }

    public function getContent()
    {
        // Return the rendered content, fallback to lb_content if available, then body
        return $this->lb_content ?? $this->body ?? '';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getTags()
    {
        return Auth::user()->note_tags()->groupBy('value')->select('value as id', 'value');
    }
}
