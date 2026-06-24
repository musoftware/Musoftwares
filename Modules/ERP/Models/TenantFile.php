<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class TenantFile extends TenantAwareModel
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


    public function uploader()
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'uploaded_by');
    }
}
