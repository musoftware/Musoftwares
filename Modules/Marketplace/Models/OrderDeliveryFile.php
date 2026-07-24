<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDeliveryFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_delivery_files';

    protected $fillable = [
        'order_id',
        'service_order_id',
        'file_path',
        'note',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'order_id');
    }
}
