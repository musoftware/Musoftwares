<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDeliveryFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_order_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function getFileSizeAttribute($value)
    {
        return number_format($value / 1024, 2).' KB';
    }

    public function getDownloadUrlAttribute()
    {
        return asset($this->file_path);
    }
}
