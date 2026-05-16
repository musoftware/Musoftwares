<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PointTransaction extends Model
{
    protected $table = 'point_transactions';
    protected $fillable = ['user_id', 'points', 'type', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
