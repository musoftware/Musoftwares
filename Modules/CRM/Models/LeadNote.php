<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;
use App\Models\User;

class LeadNote extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'lead_id',
        'workspace_id',
        'author_id',
        'note',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
