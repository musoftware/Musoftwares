<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'question',
        'answer',
        'sort_order',
    ];

    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }
}
