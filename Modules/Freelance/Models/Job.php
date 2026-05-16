<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Job extends Model
    use Searchable;
{
    use SoftDeletes;

    protected $table = 'freelance_jobs';

    protected $fillable = ['client_id', 'title', 'description', 'budget', 'currency_code', 'status'];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
