<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteService extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['primary_image_en', 'primary_image_ar'];

    public function getPrimaryImageEnAttribute()
    {
        return $this->image_path_en ?: $this->image_path_ar;
    }

    public function getPrimaryImageArAttribute()
    {
        return $this->image_path_ar ?: $this->image_path_en;
    }
}

