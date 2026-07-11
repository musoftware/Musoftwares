<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteService extends Model
{
    use HasFactory, SoftDeletes;

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
