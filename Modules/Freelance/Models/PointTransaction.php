<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PointTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'point_transactions';
    protected $fillable = ['user_id', 'points', 'type', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
