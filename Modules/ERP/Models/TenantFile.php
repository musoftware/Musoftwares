<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class TenantFile extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_tenant_files';

    protected $fillable = [
        'tenant_id',
        'storage_provider_id',
        'name',
        'path',
        'mime_type',
        'size',
        'folder',
        'uploaded_by',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'size' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function storageProvider()
    {
        return $this->belongsTo(TenantStorageProvider::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
