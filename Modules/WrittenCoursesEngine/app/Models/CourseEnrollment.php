<?php

namespace Modules\WrittenCoursesEngine\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class CourseEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'written_course_enrollments';

    protected $fillable = [
        'user_id',
        'course_slug',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
