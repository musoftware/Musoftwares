<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_tags';
    protected $guarded = [];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'taggable', 'affiliate_pos_taggables');
    }
}
