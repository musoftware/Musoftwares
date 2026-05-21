<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'question_text',
        'field_type',
        'field_options',
        'is_required',
        'sort_order',
        'placeholder',
        'help_text',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
    ];

    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }
}
