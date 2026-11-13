<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;

/** One thing the worker did on a project (took it, a step, a picture, a question, delivered, failed). */
class BuilderWorkerEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['repeats' => 'integer'];
}
