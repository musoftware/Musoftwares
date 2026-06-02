<?php

namespace Modules\WrittenCoursesEngine\app\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CourseProgress extends Model
{
    protected $table = 'written_course_progress';

    protected $fillable = [
        'user_id',
        'course_slug',
        'module_slug',
        'lesson_slug',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
