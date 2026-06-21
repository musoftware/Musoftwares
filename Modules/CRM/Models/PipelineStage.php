<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'pipeline_id',
        'name',
        'color',
        'order',
        'is_system', // e.g. won/lost stages
        'type', // 'open', 'won', 'lost'
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
