<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'language',
        'email_subject',
        'email_body',
    ];

    /**
     * Get the campaign that owns this content
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the email subject
     */
    public function getSubject()
    {
        return $this->email_subject;
    }

    /**
     * Get the email body
     */
    public function getBody()
    {
        return $this->email_body;
    }

}
