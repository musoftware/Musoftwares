<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class ServicePlaybook extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'service_playbooks';

    protected $fillable = [
        'service_id',
        'title',
        'marketing_message',
        'pricing_info',
        'client_requirements',
        'execution_workflow',
        'thank_you_message',
        'notes',
        'created_by',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
