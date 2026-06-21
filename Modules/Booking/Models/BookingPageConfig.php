<?php

namespace Modules\Booking\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BookingPageConfig extends Model
{
    use SoftDeletes;

    protected $table = 'booking_page_configs';

    protected $fillable = [
        'tenant_id',
        'slug',
        'page_title',
        'welcome_message',
        'primary_color',
        'logo_path',
        'banner_path',
        'seo_title',
        'seo_description',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
