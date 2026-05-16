<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Service extends Model
    use Searchable;
{
    use SoftDeletes;

    protected $table = 'marketplace_services';

    protected $fillable = ['seller_id', 'title', 'description', 'status'];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
