<?php

namespace Modules\WrittenCoursesEngine\App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseMetadata extends Model
{
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
