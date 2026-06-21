<?php

namespace Modules\WrittenCoursesEngine\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseMetadata extends Model
{
    use SoftDeletes;

    protected $table = 'written_courses_metadata';

    protected $fillable = [
        'slug',
        'title',
        'status',
        'difficulty',
        'tags',
        'version',
        'estimated_time',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
