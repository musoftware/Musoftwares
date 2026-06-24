<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;

class Supplier extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_suppliers';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'website',
        'tax_number',
        'status',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }
}
