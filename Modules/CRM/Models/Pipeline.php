<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'name',
        'is_default',
    ];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
