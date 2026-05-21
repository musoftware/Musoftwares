<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumToolUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'premium_tool_id',
        'used_at',
        'usage_data'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'usage_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function premiumTool()
    {
        return $this->belongsTo(PremiumTool::class);
    }
}
