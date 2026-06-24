<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_supplier_contacts';

    protected $fillable = [
        'supplier_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'designation',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
