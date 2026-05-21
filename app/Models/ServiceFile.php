<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFile extends Model
{
    protected $fillable = [
        'service_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'download_count',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
