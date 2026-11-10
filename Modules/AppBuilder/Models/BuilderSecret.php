<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One access detail for a project, for example ssh_host or ssh_password. The value is encrypted at rest. */
class BuilderSecret extends Model
{
    use SoftDeletes;

    public const FROM_CUSTOMER = 'customer';

    public const FROM_TEAM = 'team';

    protected $table = 'builder_project_secrets';

    protected $guarded = ['id'];

    protected $casts = ['value' => 'encrypted'];

    protected $hidden = ['value'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
