<?php

namespace Modules\SoftwareStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $software_product_id
 * @property string $version
 * @property string $file_path
 * @property string $file_name
 * @property string $sha512
 * @property string|null $blockmap_path
 */
class SoftwareRelease extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'released_at' => 'datetime',
        'size' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }
}
